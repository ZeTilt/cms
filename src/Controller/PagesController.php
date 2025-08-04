<?php

namespace App\Controller;

use App\Entity\Page;
use App\Entity\User;
use App\Service\PageTemplateService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Twig\Environment;

#[Route('/admin/pages')]
#[IsGranted('ROLE_ADMIN')]
class PagesController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PageTemplateService $templateService,
        private Environment $twig
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
        if ($page->getType() === 'blog') {
            return $this->render('public/blog/show.html.twig', [
                'page' => $page,
                'is_preview' => true,
            ]);
        } else {
            // Utiliser le template spécifique de la page si défini et s'il existe
            $specificTemplate = $page->getTemplatePath();
            $template = 'public/page/show.html.twig'; // template par défaut
            
            if ($specificTemplate && $this->twig->getLoader()->exists($specificTemplate)) {
                $template = $specificTemplate;
            }
            
            return $this->render($template, [
                'page' => $page,
                'is_preview' => true,
            ]);
        }
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
        
        // Always generate slug from title
        $page->generateSlug();

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