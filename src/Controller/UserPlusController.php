<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserType;
use App\Entity\UserTypeAttribute;
use App\Service\ModuleManager;
use App\Service\UserTypeManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/userplus')]
#[IsGranted('ROLE_ADMIN')]
class UserPlusController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ModuleManager $moduleManager,
        private UserTypeManager $userTypeManager
    ) {
    }

    #[Route('', name: 'admin_userplus_dashboard')]
    public function dashboard(): Response
    {
        if (!$this->moduleManager->isModuleActive('userplus')) {
            throw $this->createNotFoundException('UserPlus module is not active');
        }

        $statistics = $this->userTypeManager->getUserTypeStatistics();

        return $this->render('admin/userplus/dashboard.html.twig', [
            'statistics' => $statistics,
        ]);
    }

    // User Type Management
    #[Route('/user-types', name: 'admin_userplus_user_types')]
    public function userTypes(): Response
    {
        if (!$this->moduleManager->isModuleActive('userplus')) {
            throw $this->createNotFoundException('UserPlus module is not active');
        }

        $userTypes = $this->userTypeManager->getAllUserTypes();

        return $this->render('admin/userplus/user_types.html.twig', [
            'userTypes' => $userTypes,
        ]);
    }

    #[Route('/user-types/new', name: 'admin_userplus_user_types_new')]
    public function newUserType(): Response
    {
        if (!$this->moduleManager->isModuleActive('userplus')) {
            throw $this->createNotFoundException('UserPlus module is not active');
        }

        return $this->render('admin/userplus/user_type_edit.html.twig', [
            'userType' => new UserType(),
            'isEdit' => false,
        ]);
    }

    #[Route('/user-types/{id}/edit', name: 'admin_userplus_user_types_edit', requirements: ['id' => '\d+'])]
    public function editUserType(UserType $userType): Response
    {
        if (!$this->moduleManager->isModuleActive('userplus')) {
            throw $this->createNotFoundException('UserPlus module is not active');
        }

        return $this->render('admin/userplus/user_type_edit.html.twig', [
            'userType' => $userType,
            'isEdit' => true,
        ]);
    }

    #[Route('/user-types/save', name: 'admin_userplus_user_types_save', methods: ['POST'])]
    public function saveUserType(Request $request): Response
    {
        if (!$this->moduleManager->isModuleActive('userplus')) {
            throw $this->createNotFoundException('UserPlus module is not active');
        }

        $userTypeId = $request->request->get('id');
        $userType = $userTypeId ? $this->entityManager->getRepository(UserType::class)->find($userTypeId) : new UserType();

        if (!$userType) {
            throw $this->createNotFoundException('User type not found');
        }

        $userType->setName($request->request->get('name'));
        $userType->setDisplayName($request->request->get('display_name'));
        $userType->setDescription($request->request->get('description'));
        $userType->setActive($request->request->getBoolean('active', true));

        if (!$userTypeId) {
            $this->entityManager->persist($userType);
        }

        $this->entityManager->flush();

        $this->addFlash('success', 'User type saved successfully!');
        return $this->redirectToRoute('admin_userplus_user_types_edit', ['id' => $userType->getId()]);
    }

    #[Route('/user-types/{id}/delete', name: 'admin_userplus_user_types_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function deleteUserType(UserType $userType): Response
    {
        if (!$this->moduleManager->isModuleActive('userplus')) {
            throw $this->createNotFoundException('UserPlus module is not active');
        }

        if ($userType->getUserCount() > 0) {
            $this->addFlash('error', 'Cannot delete user type that has users assigned to it.');
            return $this->redirectToRoute('admin_userplus_user_types');
        }

        $this->entityManager->remove($userType);
        $this->entityManager->flush();

        $this->addFlash('success', 'User type deleted successfully!');
        return $this->redirectToRoute('admin_userplus_user_types');
    }

    // User Type Attributes Management
    #[Route('/user-types/{id}/attributes', name: 'admin_userplus_user_type_attributes', requirements: ['id' => '\d+'])]
    public function userTypeAttributes(UserType $userType): Response
    {
        if (!$this->moduleManager->isModuleActive('userplus')) {
            throw $this->createNotFoundException('UserPlus module is not active');
        }

        return $this->render('admin/userplus/user_type_attributes.html.twig', [
            'userType' => $userType,
        ]);
    }

    #[Route('/user-types/{id}/attributes/add', name: 'admin_userplus_add_user_type_attribute', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function addUserTypeAttribute(UserType $userType, Request $request): JsonResponse
    {
        if (!$this->moduleManager->isModuleActive('userplus')) {
            return new JsonResponse(['success' => false, 'message' => 'UserPlus module is not active'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['key']) || !isset($data['display_name']) || !isset($data['type'])) {
            return new JsonResponse(['success' => false, 'message' => 'Missing required fields'], 400);
        }

        // Check if attribute key already exists for this user type
        if ($userType->hasAttribute($data['key'])) {
            return new JsonResponse(['success' => false, 'message' => 'Attribute key already exists'], 400);
        }

        $attribute = new UserTypeAttribute();
        $attribute->setUserType($userType);
        $attribute->setAttributeKey($data['key']);
        $attribute->setDisplayName($data['display_name']);
        $attribute->setAttributeType($data['type']);
        $attribute->setRequired($data['required'] ?? false);
        $attribute->setDefaultValue($data['default_value'] ?? null);
        $attribute->setDescription($data['description'] ?? null);
        $attribute->setValidationRules($data['validation_rules'] ?? null);
        $attribute->setOptions($data['options'] ?? null);
        $attribute->setDisplayOrder($data['display_order'] ?? 0);

        $this->entityManager->persist($attribute);
        $this->entityManager->flush();

        return new JsonResponse(['success' => true, 'message' => 'Attribute added successfully']);
    }

    #[Route('/user-type-attributes/{id}/delete', name: 'admin_userplus_delete_user_type_attribute', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function deleteUserTypeAttribute(UserTypeAttribute $attribute): JsonResponse
    {
        if (!$this->moduleManager->isModuleActive('userplus')) {
            return new JsonResponse(['success' => false, 'message' => 'UserPlus module is not active'], 404);
        }

        $this->entityManager->remove($attribute);
        $this->entityManager->flush();

        return new JsonResponse(['success' => true, 'message' => 'Attribute deleted successfully']);
    }

    // User Management with Types
    #[Route('/users', name: 'admin_userplus_users')]
    public function users(Request $request): Response
    {
        if (!$this->moduleManager->isModuleActive('userplus')) {
            throw $this->createNotFoundException('UserPlus module is not active');
        }

        $page = max(1, $request->query->getInt('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $queryBuilder = $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->leftJoin('u.userType', 'ut')
            ->orderBy('u.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        $userTypeFilter = $request->query->get('user_type');
        if ($userTypeFilter) {
            $queryBuilder
                ->andWhere('u.userType = :userType')
                ->setParameter('userType', $userTypeFilter);
        }

        $users = $queryBuilder->getQuery()->getResult();

        // Count total for pagination
        $totalUsers = $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $totalPages = ceil($totalUsers / $limit);

        $userTypes = $this->userTypeManager->getAllUserTypes();

        return $this->render('admin/userplus/users.html.twig', [
            'users' => $users,
            'userTypes' => $userTypes,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalUsers' => $totalUsers,
            'currentUserType' => $userTypeFilter,
        ]);
    }

    #[Route('/users/{id}', name: 'admin_userplus_user_detail', requirements: ['id' => '\d+'])]
    public function userDetail(User $user): Response
    {
        if (!$this->moduleManager->isModuleActive('userplus')) {
            throw $this->createNotFoundException('UserPlus module is not active');
        }

        $userTypes = $this->userTypeManager->getAllUserTypes();
        
        // Validate user attributes against user type
        $validationErrors = [];
        if ($user->getUserType()) {
            $validationErrors = $this->userTypeManager->validateUserAttributes($user);
        }

        return $this->render('admin/userplus/user_detail.html.twig', [
            'user' => $user,
            'userTypes' => $userTypes,
            'validationErrors' => $validationErrors,
        ]);
    }

    #[Route('/users/{id}/assign-type', name: 'admin_userplus_assign_user_type', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function assignUserType(User $user, Request $request): JsonResponse
    {
        if (!$this->moduleManager->isModuleActive('userplus')) {
            return new JsonResponse(['success' => false, 'message' => 'UserPlus module is not active'], 404);
        }

        $userTypeId = $request->request->get('user_type_id');
        
        if (!$userTypeId) {
            $user->setUserType(null);
            $this->entityManager->flush();
            return new JsonResponse(['success' => true, 'message' => 'User type removed']);
        }

        $userType = $this->entityManager->getRepository(UserType::class)->find($userTypeId);
        if (!$userType) {
            return new JsonResponse(['success' => false, 'message' => 'User type not found'], 404);
        }

        $this->userTypeManager->assignUserType($user, $userType);

        return new JsonResponse(['success' => true, 'message' => 'User type assigned successfully']);
    }

    #[Route('/users/{id}/attributes/save', name: 'admin_userplus_save_user_attributes', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function saveUserAttributes(User $user, Request $request): JsonResponse
    {
        if (!$this->moduleManager->isModuleActive('userplus')) {
            return new JsonResponse(['success' => false, 'message' => 'UserPlus module is not active'], 404);
        }

        if (!$user->getUserType()) {
            return new JsonResponse(['success' => false, 'message' => 'User has no type assigned'], 400);
        }

        $attributes = $request->request->all('attributes');
        $errors = [];

        foreach ($user->getUserType()->getAttributes() as $typeAttribute) {
            $key = $typeAttribute->getAttributeKey();
            $value = $attributes[$key] ?? null;

            // Validate the value
            $attributeErrors = $typeAttribute->validateValue($value);
            if (!empty($attributeErrors)) {
                $errors[$key] = $attributeErrors;
                continue;
            }

            // Save the attribute
            $user->setUserAttributeValue($key, $value, $typeAttribute->getAttributeType());
        }

        if (!empty($errors)) {
            return new JsonResponse(['success' => false, 'message' => 'Validation errors', 'errors' => $errors], 400);
        }

        $this->entityManager->flush();

        return new JsonResponse(['success' => true, 'message' => 'User attributes saved successfully']);
    }

    #[Route('/users/{id}/sync-attributes', name: 'admin_userplus_sync_user_attributes', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function syncUserAttributes(User $user): JsonResponse
    {
        if (!$this->moduleManager->isModuleActive('userplus')) {
            return new JsonResponse(['success' => false, 'message' => 'UserPlus module is not active'], 404);
        }

        if (!$user->getUserType()) {
            return new JsonResponse(['success' => false, 'message' => 'User has no type assigned'], 400);
        }

        $this->userTypeManager->syncUserAttributesWithType($user);

        return new JsonResponse(['success' => true, 'message' => 'User attributes synchronized with type']);
    }
}