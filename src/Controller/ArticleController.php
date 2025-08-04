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
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/admin/articles')]
#[IsGranted('ROLE_ADMIN')]
class ArticleController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ArticleRepository $articleRepository,
        private ModuleManager $moduleManager,
        private SluggerInterface $slugger,
        private ContentSanitizer $contentSanitizer,
        private TranslatorInterface $translator
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

        // Show all articles for admins, not just their own
        $articles = $this->articleRepository->findAllForAdmin(['created_at' => 'DESC'], $limit, $offset);
        $totalArticles = $this->articleRepository->countAllForAdmin();
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
            
            $this->addFlash('success', $this->translator->trans('delete.success', [], 'articles'));
        } else {
            $this->addFlash('error', $this->translator->trans('errors.invalid_token', [], 'base'));
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
                $this->addFlash('success', $this->translator->trans('success.published', [], 'articles'));
            } else {
                $article->unpublish();
                $this->addFlash('success', $this->translator->trans('success.unpublished', [], 'articles'));
            }
            
            $this->entityManager->flush();
        } else {
            $this->addFlash('error', $this->translator->trans('errors.invalid_token', [], 'base'));
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
            $errors[] = $this->translator->trans('validation.title_required', [], 'articles');
        }

        $content = trim($request->request->get('content', ''));
        if (empty($content)) {
            $errors[] = $this->translator->trans('validation.content_required', [], 'articles');
        }

        $status = $request->request->get('status', 'draft');
        if (!in_array($status, ['draft', 'published'])) {
            $errors[] = $this->translator->trans('validation.invalid_status', [], 'articles');
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

        $this->addFlash('success', $this->translator->trans(
            $isEdit ? 'edit.success' : 'create.success',
            [],
            'articles'
        ));
        
        return $this->redirectToRoute('admin_articles_show', ['id' => $article->getId()]);
    }

    #[Route('/upload-image', name: 'admin_articles_upload_image', methods: ['POST'])]
    public function uploadImage(Request $request): Response
    {
        $uploadedFile = $request->files->get('image');
        
        if (!$uploadedFile) {
            return $this->json(['error' => 'No file uploaded'], 400);
        }

        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($uploadedFile->getMimeType(), $allowedTypes)) {
            return $this->json(['error' => 'Invalid file type. Only JPEG, PNG, GIF and WebP are allowed.'], 400);
        }

        // Validate file size (max 5MB)
        if ($uploadedFile->getSize() > 5 * 1024 * 1024) {
            return $this->json(['error' => 'File too large. Maximum size is 5MB.'], 400);
        }

        try {
            // Create uploads directory if it doesn't exist
            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/articles';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Generate unique filename
            $filename = uniqid() . '.' . $uploadedFile->guessExtension();
            $uploadedFile->move($uploadDir, $filename);

            // Return the URL for the uploaded image
            $imageUrl = '/uploads/articles/' . $filename;
            
            return $this->json(['url' => $imageUrl]);
            
        } catch (\Exception $e) {
            return $this->json(['error' => 'Upload failed: ' . $e->getMessage()], 500);
        }
    }
}