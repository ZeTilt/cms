<?php

namespace App\Controller;

use App\Entity\Page;
use App\Service\PageTemplateService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class PublicPagesController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PageTemplateService $templateService
    ) {
    }

    #[Route('/blog', name: 'public_blog_list')]
    public function blogList(): Response
    {
        $blogPosts = $this->entityManager->getRepository(Page::class)
            ->createQueryBuilder('p')
            ->where('p.type = :type')
            ->andWhere('p.status = :status')
            ->setParameter('type', 'blog')
            ->setParameter('status', 'published')
            ->orderBy('p.publishedAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('public/blog/list.html.twig', [
            'posts' => $blogPosts,
        ]);
    }

    #[Route('/blog/{slug}', name: 'public_blog_show')]
    public function blogShow(string $slug): Response
    {
        $page = $this->findPublishedPage($slug, 'blog');

        return $this->render('public/blog/show.html.twig', [
            'page' => $page,
        ]);
    }

    #[Route('/{slug}', name: 'public_page_show', priority: -1)]
    public function pageShow(string $slug): Response
    {
        $page = $this->findPublishedPage($slug, 'page');

        // Use the custom template for this page
        $templatePath = $this->templateService->getTemplatePath($page->getTemplatePath());
        
        // Check if template exists, fallback to generic template if not
        if (!$this->templateService->templateExists($page->getTemplatePath())) {
            $templatePath = 'public/page/show.html.twig';
        }

        return $this->render($templatePath, [
            'page' => $page,
        ]);
    }

    private function findPublishedPage(string $slug, ?string $type = null): Page
    {
        $qb = $this->entityManager->getRepository(Page::class)
            ->createQueryBuilder('p')
            ->where('p.slug = :slug')
            ->andWhere('p.status = :status')
            ->setParameter('slug', $slug)
            ->setParameter('status', 'published');

        if ($type) {
            $qb->andWhere('p.type = :type')
               ->setParameter('type', $type);
        }

        $page = $qb->getQuery()->getOneOrNullResult();

        if (!$page) {
            throw new NotFoundHttpException('Page not found');
        }

        return $page;
    }
}