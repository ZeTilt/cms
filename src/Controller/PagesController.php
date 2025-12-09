<?php

namespace App\Controller;

use App\Entity\ContentBlock;
use App\Entity\Page;
use App\Entity\User;
use App\Repository\ContentBlockRepository;
use App\Repository\GalleryRepository;
use App\Service\ContentSanitizer;
use App\Service\PageTemplateService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/pages')]
#[IsGranted('ROLE_ADMIN')]
class PagesController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PageTemplateService $templateService,
        private ContentBlockRepository $blockRepository,
        private ContentSanitizer $contentSanitizer,
        private GalleryRepository $galleryRepository
    ) {
    }

    #[Route('', name: 'admin_pages_list')]
    public function list(): Response
    {
        $pages = $this->entityManager->getRepository(Page::class)
            ->findBy([], ['createdAt' => 'DESC']);

        return $this->render('admin/pages/list.html.twig', [
            'pages' => $pages,
        ]);
    }

    #[Route('/new', name: 'admin_pages_new')]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->handleSave($request);
        }

        return $this->render('admin/pages/edit.html.twig', [
            'page' => null,
            'title' => 'Create New Page',
            'available_templates' => $this->templateService->getAvailableTemplates(),
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_pages_edit')]
    public function edit(Page $page, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->handleSave($request, $page);
        }

        return $this->render('admin/pages/edit.html.twig', [
            'page' => $page,
            'title' => 'Edit Page: ' . $page->getTitle(),
            'available_templates' => $this->templateService->getAvailableTemplates(),
            'template_exists' => $this->templateService->templateExists($page->getTemplatePath()),
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_pages_delete', methods: ['POST'])]
    public function delete(Page $page): Response
    {
        // Delete template file if it exists
        if ($page->getTemplatePath()) {
            $this->templateService->deleteTemplate($page->getTemplatePath());
        }

        $this->entityManager->remove($page);
        $this->entityManager->flush();

        $this->addFlash('success', 'Page and template deleted successfully.');
        return $this->redirectToRoute('admin_pages_list');
    }

    #[Route('/{id}/publish', name: 'admin_pages_publish', methods: ['POST'])]
    public function publish(Page $page): Response
    {
        $page->publish();
        $this->entityManager->flush();

        $this->addFlash('success', 'Page published successfully.');
        return $this->redirectToRoute('admin_pages_list');
    }

    #[Route('/{id}/preview', name: 'admin_pages_preview')]
    public function preview(Page $page): Response
    {
        // Render the page using the public template but with admin privileges
        return $this->render('pages/page.html.twig', [
            'page' => $page,
            'is_preview' => true,
        ]);
    }

    #[Route('/{id}/edit-blocks', name: 'admin_pages_edit_blocks', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function editBlocks(Page $page, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->handleBlocksSave($request, $page);
        }

        return $this->render('admin/pages/edit_blocks.html.twig', [
            'page' => $page,
            'widgets' => ContentBlock::WIDGETS,
            'galleries' => $this->galleryRepository->findBy([], ['title' => 'ASC']),
        ]);
    }

    #[Route('/{id}/convert-to-blocks', name: 'admin_pages_convert_to_blocks', methods: ['POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function convertToBlocks(Page $page, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('convert_blocks', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('admin_pages_edit', ['id' => $page->getId()]);
        }

        $page->setUseBlocks(true);
        $this->entityManager->flush();

        $this->addFlash('success', 'Page convertie vers l\'éditeur de blocs.');
        return $this->redirectToRoute('admin_pages_edit_blocks', ['id' => $page->getId()]);
    }

    private function handleBlocksSave(Request $request, Page $page): Response
    {
        if (!$this->isCsrfTokenValid('page_blocks', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('admin_pages_edit_blocks', ['id' => $page->getId()]);
        }

        $blocksData = json_decode($request->request->get('blocks_data', '[]'), true);

        // Remove existing blocks that are not in the new data
        $newBlockIds = [];
        foreach ($blocksData as $blockData) {
            if (is_numeric($blockData['id'])) {
                $newBlockIds[] = (int) $blockData['id'];
            }
        }

        // Delete blocks that are no longer present
        foreach ($page->getContentBlocks()->toArray() as $existingBlock) {
            if (!in_array($existingBlock->getId(), $newBlockIds)) {
                $page->removeContentBlock($existingBlock);
                $this->entityManager->remove($existingBlock);
            }
        }

        // Update or create blocks
        foreach ($blocksData as $position => $blockData) {
            $block = null;

            // Check if it's an existing block
            if (is_numeric($blockData['id'])) {
                $block = $this->blockRepository->find($blockData['id']);
            }

            if (!$block) {
                $block = new ContentBlock();
                $block->setPage($page);
                $block->setType($blockData['type']);
                $page->addContentBlock($block);
            }

            // Sanitize text content
            $data = $blockData['data'] ?? [];
            if ($blockData['type'] === ContentBlock::TYPE_TEXT && isset($data['content'])) {
                $data['content'] = $this->contentSanitizer->sanitizeContent($data['content']);
            }

            $block->setData($data);
            $block->setPosition($position);

            $this->entityManager->persist($block);
        }

        $page->setUseBlocks(true);
        $this->entityManager->flush();

        $this->addFlash('success', 'Blocs de la page mis à jour avec succès.');
        return $this->redirectToRoute('admin_pages_edit_blocks', ['id' => $page->getId()]);
    }

    private function handleSave(Request $request, ?Page $page = null): Response
    {
        $isNew = $page === null;
        
        if ($isNew) {
            $page = new Page();
            $page->setAuthor($this->getUser());
        }

        // Basic validation
        $title = $request->request->get('title');
        $templatePath = $request->request->get('template_path');
        $type = $request->request->get('type', 'page');
        $status = $request->request->get('status', 'draft');

        if (!$title) {
            $this->addFlash('error', 'Title is required.');
            return $this->render('admin/pages/edit.html.twig', [
                'page' => $page,
                'title' => $isNew ? 'Create New Page' : 'Edit Page: ' . $page->getTitle(),
                'available_templates' => $this->templateService->getAvailableTemplates(),
            ]);
        }

        $page->setTitle($title);
        $page->setType($type);
        $page->setStatus($status);
        $page->setExcerpt($request->request->get('excerpt'));
        $page->setMetaTitle($request->request->get('meta_title'));
        $page->setMetaDescription($request->request->get('meta_description'));
        
        // Generate slug if new or if title changed
        if ($isNew || $request->request->get('generate_slug')) {
            $page->generateSlug();
        } else {
            $page->setSlug($request->request->get('slug'));
        }

        // Handle template path
        if ($isNew) {
            // For new pages, create template automatically
            $templatePath = $this->templateService->createTemplate($page);
            $page->setTemplatePath($templatePath);
        } else {
            // For existing pages, update template path if changed
            if ($templatePath && $templatePath !== $page->getTemplatePath()) {
                // Rename template file if path changed
                if ($page->getTemplatePath()) {
                    $this->templateService->renameTemplate($page->getTemplatePath(), $templatePath);
                }
                $page->setTemplatePath($templatePath);
            }
        }

        // Handle tags
        $tagsString = $request->request->get('tags', '');
        $tags = array_filter(array_map('trim', explode(',', $tagsString)));
        $page->setTags($tags);

        // Handle publishing
        if ($status === 'published' && !$page->getPublishedAt()) {
            $page->setPublishedAt(new \DateTimeImmutable());
        }

        if ($isNew) {
            $this->entityManager->persist($page);
        }

        $this->entityManager->flush();

        $this->addFlash('success', $isNew ? 'Page created successfully.' : 'Page updated successfully.');
        
        return $this->redirectToRoute('admin_pages_edit', ['id' => $page->getId()]);
    }
}