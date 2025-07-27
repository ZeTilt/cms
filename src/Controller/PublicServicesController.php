<?php

namespace App\Controller;

use App\Entity\Service;
use App\Service\ModuleManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/services')]
class PublicServicesController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ModuleManager $moduleManager
    ) {
    }

    #[Route('', name: 'services_index')]
    public function index(Request $request): Response
    {
        if (!$this->moduleManager->isModuleActive('services')) {
            throw $this->createNotFoundException('Services module is not active');
        }

        $page = max(1, $request->query->getInt('page', 1));
        $limit = 12;
        $offset = ($page - 1) * $limit;

        $queryBuilder = $this->entityManager->getRepository(Service::class)
            ->createQueryBuilder('s')
            ->where('s.status = :status')
            ->setParameter('status', 'active')
            ->orderBy('s.displayOrder', 'ASC')
            ->addOrderBy('s.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        // Filter by category if provided
        $category = $request->query->get('category');
        if ($category) {
            $queryBuilder->andWhere('s.category = :category')
                ->setParameter('category', $category);
        }

        $services = $queryBuilder->getQuery()->getResult();

        // Get all categories for filter
        $categories = $this->entityManager->getRepository(Service::class)
            ->createQueryBuilder('s')
            ->select('DISTINCT s.category')
            ->where('s.status = :status')
            ->andWhere('s.category IS NOT NULL')
            ->setParameter('status', 'active')
            ->getQuery()
            ->getScalarResult();

        $categories = array_column($categories, 'category');

        // Count total for pagination
        $totalQuery = $this->entityManager->getRepository(Service::class)
            ->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.status = :status')
            ->setParameter('status', 'active');

        if ($category) {
            $totalQuery->andWhere('s.category = :category')
                ->setParameter('category', $category);
        }

        $totalServices = $totalQuery->getQuery()->getSingleScalarResult();
        $totalPages = ceil($totalServices / $limit);

        return $this->render('services/index.html.twig', [
            'services' => $services,
            'categories' => $categories,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalServices' => $totalServices,
            'currentCategory' => $category,
        ]);
    }

    #[Route('/{slug}', name: 'services_show')]
    public function show(string $slug): Response
    {
        if (!$this->moduleManager->isModuleActive('services')) {
            throw $this->createNotFoundException('Services module is not active');
        }

        $service = $this->entityManager->getRepository(Service::class)
            ->findOneBy(['slug' => $slug, 'status' => 'active']);

        if (!$service) {
            throw $this->createNotFoundException('Service not found');
        }

        // Get related services (same category, different service)
        $relatedServices = [];
        if ($service->getCategory()) {
            $relatedServices = $this->entityManager->getRepository(Service::class)
                ->createQueryBuilder('s')
                ->where('s.status = :status')
                ->andWhere('s.category = :category')
                ->andWhere('s.id != :currentId')
                ->setParameter('status', 'active')
                ->setParameter('category', $service->getCategory())
                ->setParameter('currentId', $service->getId())
                ->orderBy('s.displayOrder', 'ASC')
                ->setMaxResults(3)
                ->getQuery()
                ->getResult();
        }

        return $this->render('services/show.html.twig', [
            'service' => $service,
            'relatedServices' => $relatedServices,
        ]);
    }
}