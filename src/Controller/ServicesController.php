<?php

namespace App\Controller;

use App\Entity\Service;
use App\Service\ModuleManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/services')]
#[IsGranted('ROLE_ADMIN')]
class ServicesController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ModuleManager $moduleManager,
        private SluggerInterface $slugger
    ) {
    }

    #[Route('', name: 'admin_services_list')]
    public function list(Request $request): Response
    {
        if (!$this->moduleManager->isModuleActive('services')) {
            throw $this->createNotFoundException('Services module is not active');
        }

        $page = max(1, $request->query->getInt('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $queryBuilder = $this->entityManager->getRepository(Service::class)
            ->createQueryBuilder('s')
            ->orderBy('s.displayOrder', 'ASC')
            ->addOrderBy('s.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        // Filters
        $status = $request->query->get('status');
        if ($status) {
            $queryBuilder->andWhere('s.status = :status')
                ->setParameter('status', $status);
        }

        $category = $request->query->get('category');
        if ($category) {
            $queryBuilder->andWhere('s.category = :category')
                ->setParameter('category', $category);
        }

        $search = $request->query->get('search');
        if ($search) {
            $queryBuilder->andWhere('LOWER(s.name) LIKE :search OR LOWER(s.description) LIKE :search')
                ->setParameter('search', '%' . strtolower($search) . '%');
        }

        // Count total for pagination
        $countQueryBuilder = clone $queryBuilder;
        $totalServices = $countQueryBuilder
            ->select('COUNT(s.id)')
            ->setFirstResult(null)
            ->setMaxResults(null)
            ->getQuery()
            ->getSingleScalarResult();

        $services = $queryBuilder->getQuery()->getResult();
        $totalPages = ceil($totalServices / $limit);

        // Get categories for filter
        $categories = $this->entityManager->getRepository(Service::class)
            ->createQueryBuilder('s')
            ->select('DISTINCT s.category')
            ->where('s.category IS NOT NULL')
            ->orderBy('s.category', 'ASC')
            ->getQuery()
            ->getScalarResult();

        return $this->render('admin/services/list.html.twig', [
            'services' => $services,
            'categories' => array_column($categories, 'category'),
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalServices' => $totalServices,
            'currentStatus' => $status,
            'currentCategory' => $category,
            'currentSearch' => $search,
        ]);
    }

    #[Route('/new', name: 'admin_services_new')]
    public function new(): Response
    {
        if (!$this->moduleManager->isModuleActive('services')) {
            throw $this->createNotFoundException('Services module is not active');
        }

        return $this->render('admin/services/edit.html.twig', [
            'service' => new Service(),
            'isEdit' => false,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_services_edit', requirements: ['id' => '\d+'])]
    public function edit(Service $service): Response
    {
        if (!$this->moduleManager->isModuleActive('services')) {
            throw $this->createNotFoundException('Services module is not active');
        }

        return $this->render('admin/services/edit.html.twig', [
            'service' => $service,
            'isEdit' => true,
        ]);
    }

    #[Route('/save', name: 'admin_services_save', methods: ['POST'])]
    public function save(Request $request): Response
    {
        if (!$this->moduleManager->isModuleActive('services')) {
            throw $this->createNotFoundException('Services module is not active');
        }

        $serviceId = $request->request->get('id');
        $service = $serviceId ? $this->entityManager->getRepository(Service::class)->find($serviceId) : new Service();

        if (!$service) {
            throw $this->createNotFoundException('Service not found');
        }

        // Basic fields
        $service->setName($request->request->get('name'));
        
        // Set slug (use provided slug or generate from name)
        $slug = $request->request->get('slug');
        if (empty($slug)) {
            $slug = $this->slugger->slug($request->request->get('name'))->lower();
        } else {
            $slug = $this->slugger->slug($slug)->lower();
        }
        $service->setSlug($slug);

        $service->setDescription($request->request->get('description'));
        $service->setShortDescription($request->request->get('short_description'));
        $service->setCategory($request->request->get('category'));
        $service->setStatus($request->request->get('status', 'draft'));

        // Pricing
        $price = $request->request->get('price');
        if ($price) {
            $service->setPrice($price);
        }
        $service->setCurrency($request->request->get('currency', 'EUR'));
        $service->setPricingType($request->request->get('pricing_type', 'fixed'));

        // Duration
        $duration = $request->request->get('duration');
        if ($duration) {
            $service->setDuration($duration);
        }

        // Features
        $featuresString = $request->request->get('features', '');
        $features = $featuresString ? array_map('trim', explode("\n", $featuresString)) : [];
        $service->setFeatures(array_filter($features));

        // Settings
        $service->setBookable($request->request->getBoolean('bookable'));
        $service->setFeatured($request->request->getBoolean('featured'));
        
        $displayOrder = $request->request->get('display_order');
        if ($displayOrder !== null) {
            $service->setDisplayOrder((int) $displayOrder);
        }

        // Update timestamp
        if ($serviceId) {
            $service->setUpdatedAt(new \DateTimeImmutable());
        }

        if (!$serviceId) {
            $this->entityManager->persist($service);
        }

        $this->entityManager->flush();

        $this->addFlash('success', 'Service saved successfully!');
        return $this->redirectToRoute('admin_services_edit', ['id' => $service->getId()]);
    }

    #[Route('/{id}/delete', name: 'admin_services_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Service $service): Response
    {
        if (!$this->moduleManager->isModuleActive('services')) {
            throw $this->createNotFoundException('Services module is not active');
        }

        $this->entityManager->remove($service);
        $this->entityManager->flush();

        $this->addFlash('success', 'Service deleted successfully!');
        return $this->redirectToRoute('admin_services_list');
    }

    #[Route('/{id}/toggle-status', name: 'admin_services_toggle_status', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleStatus(Service $service): Response
    {
        if (!$this->moduleManager->isModuleActive('services')) {
            throw $this->createNotFoundException('Services module is not active');
        }

        $newStatus = $service->getStatus() === 'active' ? 'inactive' : 'active';
        $service->setStatus($newStatus);
        $service->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->flush();

        $this->addFlash('success', 'Service status updated successfully!');
        return $this->redirectToRoute('admin_services_list');
    }
}