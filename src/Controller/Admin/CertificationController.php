<?php

namespace App\Controller\Admin;

use App\Entity\Certification;
use App\Entity\UserCertification;
use App\Entity\User;
use App\Service\ModuleManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/certifications')]
#[IsGranted('ROLE_ADMIN')]
class CertificationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ModuleManager $moduleManager
    ) {
    }

    #[Route('', name: 'admin_certifications_index')]
    public function index(Request $request): Response
    {
        if (!$this->moduleManager->isModuleActive('certifications')) {
            throw $this->createNotFoundException('Certifications module is not active');
        }

        $page = max(1, $request->query->getInt('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $queryBuilder = $this->entityManager->getRepository(Certification::class)
            ->createQueryBuilder('c')
            ->orderBy('c.name', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        // Search filter
        $search = $request->query->get('search');
        if ($search) {
            $queryBuilder->andWhere('c.name LIKE :search OR c.description LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        // Level filter
        $level = $request->query->get('level');
        if ($level) {
            $queryBuilder->andWhere('c.level = :level')
                ->setParameter('level', $level);
        }

        // Status filter
        $isActive = $request->query->get('active');
        if ($isActive !== null) {
            $queryBuilder->andWhere('c.isActive = :isActive')
                ->setParameter('isActive', $isActive === '1');
        }

        $certifications = $queryBuilder->getQuery()->getResult();

        // Count total for pagination
        $totalQuery = $this->entityManager->getRepository(Certification::class)
            ->createQueryBuilder('c')
            ->select('COUNT(c.id)');
        
        if ($search) {
            $totalQuery->andWhere('c.name LIKE :search OR c.description LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }
        
        if ($level) {
            $totalQuery->andWhere('c.level = :level')
                ->setParameter('level', $level);
        }
        
        if ($isActive !== null) {
            $totalQuery->andWhere('c.isActive = :isActive')
                ->setParameter('isActive', $isActive === '1');
        }

        $totalCertifications = $totalQuery->getQuery()->getSingleScalarResult();
        $totalPages = ceil($totalCertifications / $limit);

        // Get statistics
        $stats = $this->getCertificationStatistics();

        return $this->render('admin/certifications/index.html.twig', [
            'certifications' => $certifications,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalCertifications' => $totalCertifications,
            'currentSearch' => $search,
            'currentLevel' => $level,
            'currentActive' => $isActive,
            'stats' => $stats,
        ]);
    }

    #[Route('/new', name: 'admin_certifications_new')]
    public function new(Request $request): Response
    {
        if (!$this->moduleManager->isModuleActive('certifications')) {
            throw $this->createNotFoundException('Certifications module is not active');
        }

        if ($request->isMethod('POST')) {
            $certification = new Certification();
            $certification->setName($request->request->get('name', ''));
            $certification->setDescription($request->request->get('description', ''));
            $certification->setLevel($request->request->get('level', 1));
            $certification->setIsActive($request->request->getBoolean('is_active', true));
            
            $validityMonths = $request->request->get('validity_months');
            if ($validityMonths) {
                $certification->setValidityMonths((int) $validityMonths);
            }

            $prerequisites = $request->request->get('prerequisites');
            if ($prerequisites) {
                $prerequisiteIds = array_map('intval', explode(',', $prerequisites));
                $prerequisiteEntities = $this->entityManager->getRepository(Certification::class)
                    ->findBy(['id' => $prerequisiteIds]);
                foreach ($prerequisiteEntities as $prerequisite) {
                    $certification->addPrerequisite($prerequisite);
                }
            }

            $this->entityManager->persist($certification);
            $this->entityManager->flush();

            $this->addFlash('success', 'Certification created successfully.');
            return $this->redirectToRoute('admin_certifications_index');
        }

        $availablePrerequisites = $this->entityManager->getRepository(Certification::class)
            ->findBy(['isActive' => true], ['name' => 'ASC']);

        return $this->render('admin/certifications/edit.html.twig', [
            'certification' => new Certification(),
            'availablePrerequisites' => $availablePrerequisites,
            'isNew' => true,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_certifications_edit')]
    public function edit(int $id, Request $request): Response
    {
        if (!$this->moduleManager->isModuleActive('certifications')) {
            throw $this->createNotFoundException('Certifications module is not active');
        }

        $certification = $this->entityManager->getRepository(Certification::class)->find($id);
        if (!$certification) {
            throw $this->createNotFoundException('Certification not found');
        }

        if ($request->isMethod('POST')) {
            $certification->setName($request->request->get('name', ''));
            $certification->setDescription($request->request->get('description', ''));
            $certification->setLevel($request->request->get('level', 1));
            $certification->setIsActive($request->request->getBoolean('is_active', true));
            
            $validityMonths = $request->request->get('validity_months');
            if ($validityMonths) {
                $certification->setValidityMonths((int) $validityMonths);
            } else {
                $certification->setValidityMonths(null);
            }

            // Clear existing prerequisites
            foreach ($certification->getPrerequisites() as $prerequisite) {
                $certification->removePrerequisite($prerequisite);
            }

            // Add new prerequisites
            $prerequisites = $request->request->get('prerequisites');
            if ($prerequisites) {
                $prerequisiteIds = array_map('intval', explode(',', $prerequisites));
                $prerequisiteEntities = $this->entityManager->getRepository(Certification::class)
                    ->findBy(['id' => $prerequisiteIds]);
                foreach ($prerequisiteEntities as $prerequisite) {
                    // Prevent self-reference
                    if ($prerequisite->getId() !== $certification->getId()) {
                        $certification->addPrerequisite($prerequisite);
                    }
                }
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Certification updated successfully.');
            return $this->redirectToRoute('admin_certifications_index');
        }

        $availablePrerequisites = $this->entityManager->getRepository(Certification::class)
            ->createQueryBuilder('c')
            ->where('c.isActive = :active')
            ->andWhere('c.id != :currentId')
            ->setParameter('active', true)
            ->setParameter('currentId', $certification->getId())
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('admin/certifications/edit.html.twig', [
            'certification' => $certification,
            'availablePrerequisites' => $availablePrerequisites,
            'isNew' => false,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_certifications_delete', methods: ['POST'])]
    public function delete(int $id, Request $request): Response
    {
        if (!$this->moduleManager->isModuleActive('certifications')) {
            throw $this->createNotFoundException('Certifications module is not active');
        }

        $certification = $this->entityManager->getRepository(Certification::class)->find($id);
        if (!$certification) {
            throw $this->createNotFoundException('Certification not found');
        }

        // Check if certification is used by users
        $userCertificationsCount = $this->entityManager->getRepository(UserCertification::class)
            ->count(['certification' => $certification]);

        if ($userCertificationsCount > 0) {
            $this->addFlash('error', 'Cannot delete certification: it is assigned to users. Deactivate it instead.');
            return $this->redirectToRoute('admin_certifications_index');
        }

        // Verify CSRF token
        if (!$this->isCsrfTokenValid('delete_certification_' . $certification->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid security token.');
            return $this->redirectToRoute('admin_certifications_index');
        }

        $this->entityManager->remove($certification);
        $this->entityManager->flush();

        $this->addFlash('success', 'Certification deleted successfully.');
        return $this->redirectToRoute('admin_certifications_index');
    }

    #[Route('/{id}/users', name: 'admin_certifications_users')]
    public function users(int $id, Request $request): Response
    {
        if (!$this->moduleManager->isModuleActive('certifications')) {
            throw $this->createNotFoundException('Certifications module is not active');
        }

        $certification = $this->entityManager->getRepository(Certification::class)->find($id);
        if (!$certification) {
            throw $this->createNotFoundException('Certification not found');
        }

        $page = max(1, $request->query->getInt('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $queryBuilder = $this->entityManager->getRepository(UserCertification::class)
            ->createQueryBuilder('uc')
            ->leftJoin('uc.user', 'u')
            ->where('uc.certification = :certification')
            ->setParameter('certification', $certification)
            ->orderBy('uc.obtainedAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        // Status filter
        $status = $request->query->get('status');
        if ($status) {
            if ($status === 'expired') {
                $queryBuilder->andWhere('uc.expiresAt < :now')
                    ->setParameter('now', new \DateTimeImmutable());
            } elseif ($status === 'expiring_soon') {
                $queryBuilder->andWhere('uc.expiresAt > :now AND uc.expiresAt < :soon')
                    ->setParameter('now', new \DateTimeImmutable())
                    ->setParameter('soon', new \DateTimeImmutable('+30 days'));
            } else {
                $queryBuilder->andWhere('uc.status = :status')
                    ->setParameter('status', $status);
            }
        }

        $userCertifications = $queryBuilder->getQuery()->getResult();

        // Count total for pagination
        $totalQuery = $this->entityManager->getRepository(UserCertification::class)
            ->createQueryBuilder('uc')
            ->select('COUNT(uc.id)')
            ->where('uc.certification = :certification')
            ->setParameter('certification', $certification);

        if ($status) {
            if ($status === 'expired') {
                $totalQuery->andWhere('uc.expiresAt < :now')
                    ->setParameter('now', new \DateTimeImmutable());
            } elseif ($status === 'expiring_soon') {
                $totalQuery->andWhere('uc.expiresAt > :now AND uc.expiresAt < :soon')
                    ->setParameter('now', new \DateTimeImmutable())
                    ->setParameter('soon', new \DateTimeImmutable('+30 days'));
            } else {
                $totalQuery->andWhere('uc.status = :status')
                    ->setParameter('status', $status);
            }
        }

        $totalUserCertifications = $totalQuery->getQuery()->getSingleScalarResult();
        $totalPages = ceil($totalUserCertifications / $limit);

        return $this->render('admin/certifications/users.html.twig', [
            'certification' => $certification,
            'userCertifications' => $userCertifications,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalUserCertifications' => $totalUserCertifications,
            'currentStatus' => $status,
        ]);
    }

    #[Route('/user-certifications', name: 'admin_user_certifications_index')]
    public function userCertificationsIndex(Request $request): Response
    {
        if (!$this->moduleManager->isModuleActive('certifications')) {
            throw $this->createNotFoundException('Certifications module is not active');
        }

        $page = max(1, $request->query->getInt('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $queryBuilder = $this->entityManager->getRepository(UserCertification::class)
            ->createQueryBuilder('uc')
            ->leftJoin('uc.user', 'u')
            ->leftJoin('uc.certification', 'c')
            ->orderBy('uc.obtainedAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        // User filter
        $userId = $request->query->get('user');
        if ($userId) {
            $queryBuilder->andWhere('u.id = :userId')
                ->setParameter('userId', $userId);
        }

        // Certification filter
        $certificationId = $request->query->get('certification');
        if ($certificationId) {
            $queryBuilder->andWhere('c.id = :certificationId')
                ->setParameter('certificationId', $certificationId);
        }

        // Status filter
        $status = $request->query->get('status');
        if ($status) {
            if ($status === 'expired') {
                $queryBuilder->andWhere('uc.expiresAt < :now')
                    ->setParameter('now', new \DateTimeImmutable());
            } elseif ($status === 'expiring_soon') {
                $queryBuilder->andWhere('uc.expiresAt > :now AND uc.expiresAt < :soon')
                    ->setParameter('now', new \DateTimeImmutable())
                    ->setParameter('soon', new \DateTimeImmutable('+30 days'));
            } else {
                $queryBuilder->andWhere('uc.status = :status')
                    ->setParameter('status', $status);
            }
        }

        $userCertifications = $queryBuilder->getQuery()->getResult();

        // Count total for pagination
        $totalQuery = $this->entityManager->getRepository(UserCertification::class)
            ->createQueryBuilder('uc')
            ->leftJoin('uc.user', 'u')
            ->leftJoin('uc.certification', 'c')
            ->select('COUNT(uc.id)');

        if ($userId) {
            $totalQuery->andWhere('u.id = :userId')
                ->setParameter('userId', $userId);
        }

        if ($certificationId) {
            $totalQuery->andWhere('c.id = :certificationId')
                ->setParameter('certificationId', $certificationId);
        }

        if ($status) {
            if ($status === 'expired') {
                $totalQuery->andWhere('uc.expiresAt < :now')
                    ->setParameter('now', new \DateTimeImmutable());
            } elseif ($status === 'expiring_soon') {
                $totalQuery->andWhere('uc.expiresAt > :now AND uc.expiresAt < :soon')
                    ->setParameter('now', new \DateTimeImmutable())
                    ->setParameter('soon', new \DateTimeImmutable('+30 days'));
            } else {
                $totalQuery->andWhere('uc.status = :status')
                    ->setParameter('status', $status);
            }
        }

        $totalUserCertifications = $totalQuery->getQuery()->getSingleScalarResult();
        $totalPages = ceil($totalUserCertifications / $limit);

        // Get options for filters
        $users = $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->orderBy('u.firstName', 'ASC')
            ->addOrderBy('u.lastName', 'ASC')
            ->getQuery()
            ->getResult();

        $certifications = $this->entityManager->getRepository(Certification::class)
            ->findBy(['isActive' => true], ['name' => 'ASC']);

        return $this->render('admin/certifications/user_certifications.html.twig', [
            'userCertifications' => $userCertifications,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalUserCertifications' => $totalUserCertifications,
            'currentUser' => $userId,
            'currentCertification' => $certificationId,
            'currentStatus' => $status,
            'users' => $users,
            'certifications' => $certifications,
        ]);
    }

    private function getCertificationStatistics(): array
    {
        $totalCertifications = $this->entityManager->getRepository(Certification::class)
            ->count([]);

        $activeCertifications = $this->entityManager->getRepository(Certification::class)
            ->count(['isActive' => true]);

        $totalUserCertifications = $this->entityManager->getRepository(UserCertification::class)
            ->count([]);

        $activeUserCertifications = $this->entityManager->getRepository(UserCertification::class)
            ->count(['status' => 'active']);

        $expiredCount = $this->entityManager->getRepository(UserCertification::class)
            ->createQueryBuilder('uc')
            ->select('COUNT(uc.id)')
            ->where('uc.expiresAt < :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getSingleScalarResult();

        $expiringSoonCount = $this->entityManager->getRepository(UserCertification::class)
            ->createQueryBuilder('uc')
            ->select('COUNT(uc.id)')
            ->where('uc.expiresAt > :now AND uc.expiresAt < :soon')
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('soon', new \DateTimeImmutable('+30 days'))
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'totalCertifications' => $totalCertifications,
            'activeCertifications' => $activeCertifications,
            'totalUserCertifications' => $totalUserCertifications,
            'activeUserCertifications' => $activeUserCertifications,
            'expiredCount' => $expiredCount,
            'expiringSoonCount' => $expiringSoonCount,
        ];
    }
}