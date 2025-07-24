<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use App\Service\ModuleManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/blog')]
class BlogController extends AbstractController
{
    public function __construct(
        private ArticleRepository $articleRepository,
        private ModuleManager $moduleManager
    ) {}

    #[Route('', name: 'blog_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        if (!$this->moduleManager->isModuleActive('blog')) {
            throw $this->createNotFoundException('Blog is not available');
        }

        $page = max(1, $request->query->getInt('page', 1));
        $limit = 6; // Articles per page
        $offset = ($page - 1) * $limit;

        $articles = $this->articleRepository->findPublished($limit, $offset);
        $totalArticles = $this->articleRepository->countPublished();
        $totalPages = (int) ceil($totalArticles / $limit);

        return $this->render('blog/index.html.twig', [
            'articles' => $articles,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_articles' => $totalArticles,
        ]);
    }

    #[Route('/article/{slug}', name: 'blog_article', methods: ['GET'])]
    public function article(string $slug): Response
    {
        if (!$this->moduleManager->isModuleActive('blog')) {
            throw $this->createNotFoundException('Blog is not available');
        }

        $article = $this->articleRepository->findOneBy([
            'slug' => $slug,
            'status' => 'published'
        ]);

        if (!$article || !$article->isPublished()) {
            throw $this->createNotFoundException('Article not found');
        }

        return $this->render('blog/article.html.twig', [
            'article' => $article,
        ]);
    }

    #[Route('/category/{category}', name: 'blog_category', methods: ['GET'])]
    public function category(string $category, Request $request): Response
    {
        if (!$this->moduleManager->isModuleActive('blog')) {
            throw $this->createNotFoundException('Blog is not available');
        }

        $articles = $this->articleRepository->findPublishedByCategory($category);

        return $this->render('blog/category.html.twig', [
            'articles' => $articles,
            'category' => $category,
        ]);
    }

    #[Route('/tag/{tag}', name: 'blog_tag', methods: ['GET'])]
    public function tag(string $tag, Request $request): Response
    {
        if (!$this->moduleManager->isModuleActive('blog')) {
            throw $this->createNotFoundException('Blog is not available');
        }

        $articles = $this->articleRepository->findPublishedByTag($tag);

        return $this->render('blog/tag.html.twig', [
            'articles' => $articles,
            'tag' => $tag,
        ]);
    }
}