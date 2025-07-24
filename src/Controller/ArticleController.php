<?php

namespace App\Controller;

use App\Entity\Article;
use App\Repository\ArticleRepository;
use App\Service\ModuleManager;
use App\Service\ContentSanitizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/articles')]
#[IsGranted('ROLE_ADMIN')]
class ArticleController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ArticleRepository $articleRepository,
        private ModuleManager $moduleManager,
        private SluggerInterface $slugger,
        private ContentSanitizer $contentSanitizer
    ) {}

    #[Route('', name: 'admin_articles_list', methods: ['GET'])]
    public function index(Request $request): Response
    {
        if (!$this->moduleManager->isModuleActive('blog')) {
            throw $this->createNotFoundException('Blog module is not active');
        }

        $page = max(1, $request->query->getInt('page', 1));
        $limit = 10; // Articles per page in admin
        $offset = ($page - 1) * $limit;

        $articles = $this->articleRepository->findByAuthor($this->getUser(), ['created_at' => 'DESC'], $limit, $offset);
        $totalArticles = $this->articleRepository->countByAuthor($this->getUser());
        $totalPages = (int) ceil($totalArticles / $limit);

        return $this->render('admin/articles/index.html.twig', [
            'articles' => $articles,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_articles' => $totalArticles,
        ]);
    }

    #[Route('/new', name: 'admin_articles_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if (!$this->moduleManager->isModuleActive('blog')) {
            throw $this->createNotFoundException('Blog module is not active');
        }

        if ($request->isMethod('POST')) {
            $result = $this->handleSave($request);
            if ($result instanceof Response) {
                return $result;
            }
        }

        return $this->render('admin/articles/edit.html.twig', [
            'article' => new Article(),
            'isEdit' => false
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_articles_edit', methods: ['GET', 'POST'])]
    public function edit(Article $article, Request $request): Response
    {
        if (!$this->moduleManager->isModuleActive('blog')) {
            throw $this->createNotFoundException('Blog module is not active');
        }

        if ($request->isMethod('POST')) {
            $result = $this->handleSave($request, $article);
            if ($result instanceof Response) {
                return $result;
            }
        }

        return $this->render('admin/articles/edit.html.twig', [
            'article' => $article,
            'isEdit' => true
        ]);
    }

    #[Route('/{id}', name: 'admin_articles_show', methods: ['GET'])]
    public function show(Article $article): Response
    {
        if (!$this->moduleManager->isModuleActive('blog')) {
            throw $this->createNotFoundException('Blog module is not active');
        }

        return $this->render('admin/articles/show.html.twig', [
            'article' => $article
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_articles_delete', methods: ['POST'])]
    public function delete(Article $article, Request $request): Response
    {
        if (!$this->moduleManager->isModuleActive('blog')) {
            throw $this->createNotFoundException('Blog module is not active');
        }

        if ($this->isCsrfTokenValid('delete_article', $request->request->get('_token'))) {
            $this->entityManager->remove($article);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Article deleted successfully!');
        } else {
            $this->addFlash('error', 'Invalid security token.');
        }

        return $this->redirectToRoute('admin_articles_list');
    }

    #[Route('/{id}/toggle-status', name: 'admin_articles_toggle_status', methods: ['POST'])]
    public function toggleStatus(Article $article, Request $request): Response
    {
        if (!$this->moduleManager->isModuleActive('blog')) {
            throw $this->createNotFoundException('Blog module is not active');
        }

        if ($this->isCsrfTokenValid('toggle_status', $request->request->get('_token'))) {
            if ($article->isDraft()) {
                $article->publish();
                $this->addFlash('success', 'Article published successfully!');
            } else {
                $article->unpublish();
                $this->addFlash('success', 'Article unpublished successfully!');
            }
            
            $this->entityManager->flush();
        } else {
            $this->addFlash('error', 'Invalid security token.');
        }

        return $this->redirectToRoute('admin_articles_show', ['id' => $article->getId()]);
    }

    private function handleSave(Request $request, Article $article = null): ?Response
    {
        $isEdit = $article !== null;
        if (!$isEdit) {
            $article = new Article();
        }

        // Validation
        $errors = [];
        
        $title = trim($request->request->get('title', ''));
        if (empty($title)) {
            $errors[] = 'Title is required.';
        }

        $content = trim($request->request->get('content', ''));
        if (empty($content)) {
            $errors[] = 'Content is required.';
        }

        $status = $request->request->get('status', 'draft');
        if (!in_array($status, ['draft', 'published'])) {
            $errors[] = 'Invalid status.';
        }

        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
            return null;
        }

        // Sanitize content for security
        $sanitizedContent = $this->contentSanitizer->sanitizeContent($content);
        $excerpt = trim($request->request->get('excerpt', ''));
        
        // Auto-generate excerpt if empty
        if (empty($excerpt)) {
            $excerpt = $this->contentSanitizer->generateExcerpt($sanitizedContent, 160);
        }

        // Update article
        $article->setTitle($title);
        $article->setContent($sanitizedContent);
        $article->setExcerpt($excerpt);
        $article->setCategory($request->request->get('category', ''));
        $article->setStatus($status);

        // Handle tags
        $tagsString = $request->request->get('tags', '');
        if (!empty($tagsString)) {
            $tags = array_map('trim', explode(',', $tagsString));
            $tags = array_filter($tags); // Remove empty tags
            $article->setTags($tags);
        } else {
            $article->setTags([]);
        }

        // Generate slug
        $article->generateSlug($this->slugger);

        // Set author for new articles
        if (!$isEdit) {
            $article->setAuthor($this->getUser());
        }

        // Set published date if publishing
        if ($status === 'published' && $article->getPublishedAt() === null) {
            $article->setPublishedAt(new \DateTime());
        }

        // Save article
        $this->entityManager->persist($article);
        $this->entityManager->flush();

        $this->addFlash('success', ($isEdit ? 'Article updated' : 'Article created') . ' successfully!');
        
        return $this->redirectToRoute('admin_articles_show', ['id' => $article->getId()]);
    }
}