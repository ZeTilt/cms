<?php

namespace App\Controller;

use App\Entity\Page;
use App\Entity\User;
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
        private EntityManagerInterface $entityManager
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
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_pages_delete', methods: ['POST'])]
    public function delete(Page $page): Response
    {
        $this->entityManager->remove($page);
        $this->entityManager->flush();

        $this->addFlash('success', 'Page deleted successfully.');
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

    private function handleSave(Request $request, ?Page $page = null): Response
    {
        $isNew = $page === null;
        
        if ($isNew) {
            $page = new Page();
            $page->setAuthor($this->getUser());
        }

        // Basic validation
        $title = $request->request->get('title');
        $content = $request->request->get('content');
        $type = $request->request->get('type', 'page');
        $status = $request->request->get('status', 'draft');

        if (!$title || !$content) {
            $this->addFlash('error', 'Title and content are required.');
            return $this->render('admin/pages/edit.html.twig', [
                'page' => $page,
                'title' => $isNew ? 'Create New Page' : 'Edit Page: ' . $page->getTitle(),
            ]);
        }

        $page->setTitle($title);
        $page->setContent($content);
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