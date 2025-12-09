<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\ContentBlock;
use App\Repository\ArticleRepository;
use App\Repository\ContentBlockRepository;
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
        private ContentBlockRepository $blockRepository,
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

        // Use block editor by default for new articles
        return $this->render('admin/articles/edit_blocks.html.twig', [
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

        // Use block editor if article uses blocks, otherwise classic editor
        $template = $article->getUseBlocks() ? 'admin/articles/edit_blocks.html.twig' : 'admin/articles/edit.html.twig';

        return $this->render($template, [
            'article' => $article,
            'isEdit' => true
        ]);
    }

    #[Route('/{id}/convert-to-blocks', name: 'admin_articles_convert_to_blocks', methods: ['POST'])]
    public function convertToBlocks(Article $article, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('convert_blocks', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('admin_articles_edit', ['id' => $article->getId()]);
        }

        // Create a text block with existing content
        if (!empty($article->getContent())) {
            $block = new ContentBlock();
            $block->setArticle($article);
            $block->setType(ContentBlock::TYPE_TEXT);
            $block->setData(['content' => $article->getContent()]);
            $block->setPosition(0);
            $this->entityManager->persist($block);
        }

        $article->setUseBlocks(true);
        $this->entityManager->flush();

        $this->addFlash('success', 'Article converti vers l\'éditeur de blocs.');
        return $this->redirectToRoute('admin_articles_edit', ['id' => $article->getId()]);
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

        $useBlocks = $request->request->get('use_blocks') === '1';

        // Validation
        $errors = [];

        $title = trim($request->request->get('title', ''));
        if (empty($title)) {
            $errors[] = 'Le titre est requis.';
        }

        // For block-based editor, content is in blocks_data
        if ($useBlocks) {
            $blocksData = $request->request->get('blocks_data', '');
            $blocks = json_decode($blocksData, true) ?? [];
            if (empty($blocks)) {
                $errors[] = 'Au moins un bloc de contenu est requis.';
            }
        } else {
            $content = trim($request->request->get('content', ''));
            if (empty($content)) {
                $errors[] = 'Le contenu est requis.';
            }
        }

        $status = $request->request->get('status', 'draft');
        if (!in_array($status, ['draft', 'published'])) {
            $errors[] = 'Statut invalide.';
        }

        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
            return null;
        }

        // Update article basic fields
        $article->setTitle($title);
        $article->setStatus($status);
        $article->setCategory($request->request->get('category', ''));

        // Handle excerpt
        $excerpt = trim($request->request->get('excerpt', ''));

        // Handle featured image
        $featuredImage = $request->request->get('featured_image', '');
        $article->setFeaturedImage($featuredImage ?: null);
        $article->setFeaturedImageAlt($request->request->get('featured_image_alt', ''));
        $article->setFeaturedImageCaption($request->request->get('featured_image_caption', ''));

        // Handle tags
        $tagsString = $request->request->get('tags', '');
        if (!empty($tagsString)) {
            $tags = array_map('trim', explode(',', $tagsString));
            $tags = array_filter($tags);
            $article->setTags($tags);
        } else {
            $article->setTags([]);
        }

        if ($useBlocks) {
            $article->setUseBlocks(true);

            // Process blocks
            $blocksData = json_decode($request->request->get('blocks_data', '[]'), true);

            // Remove existing blocks that are not in the new data
            $existingBlockIds = [];
            foreach ($article->getContentBlocks() as $existingBlock) {
                $existingBlockIds[] = $existingBlock->getId();
            }

            $newBlockIds = [];
            foreach ($blocksData as $blockData) {
                if (is_numeric($blockData['id'])) {
                    $newBlockIds[] = (int) $blockData['id'];
                }
            }

            // Delete blocks that are no longer present
            foreach ($article->getContentBlocks()->toArray() as $existingBlock) {
                if (!in_array($existingBlock->getId(), $newBlockIds)) {
                    $article->removeContentBlock($existingBlock);
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
                    $block->setArticle($article);
                    $block->setType($blockData['type']);
                    $article->addContentBlock($block);
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

            // Generate content from blocks for excerpt and search
            $contentParts = [];
            foreach ($blocksData as $blockData) {
                if ($blockData['type'] === ContentBlock::TYPE_TEXT && isset($blockData['data']['content'])) {
                    $contentParts[] = $blockData['data']['content'];
                } elseif ($blockData['type'] === ContentBlock::TYPE_QUOTE && isset($blockData['data']['text'])) {
                    $contentParts[] = $blockData['data']['text'];
                }
            }
            $generatedContent = implode("\n", $contentParts);
            $article->setContent($generatedContent);

            // Auto-generate excerpt if empty
            if (empty($excerpt) && !empty($generatedContent)) {
                $excerpt = $this->contentSanitizer->generateExcerpt($generatedContent, 160);
            }
        } else {
            // Classic editor
            $content = trim($request->request->get('content', ''));
            $sanitizedContent = $this->contentSanitizer->sanitizeContent($content);
            $article->setContent($sanitizedContent);

            // Auto-generate excerpt if empty
            if (empty($excerpt)) {
                $excerpt = $this->contentSanitizer->generateExcerpt($sanitizedContent, 160);
            }
        }

        $article->setExcerpt($excerpt);

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

        $this->addFlash('success', ($isEdit ? 'Article mis à jour' : 'Article créé') . ' avec succès !');

        return $this->redirectToRoute('admin_articles_show', ['id' => $article->getId()]);
    }
}