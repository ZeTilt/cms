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
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/admin/userplus')]
#[IsGranted('ROLE_ADMIN')]
class UserPlusController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ModuleManager $moduleManager,
        private UserTypeManager $userTypeManager,
        private UserPasswordHasherInterface $passwordHasher
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

    // User Type Management (SUPER_ADMIN only)
    #[Route('/user-types', name: 'admin_userplus_user_types')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
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
    #[IsGranted('ROLE_SUPER_ADMIN')]
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
    #[IsGranted('ROLE_SUPER_ADMIN')]
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
    #[IsGranted('ROLE_SUPER_ADMIN')]
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
    #[IsGranted('ROLE_SUPER_ADMIN')]
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

    // User Type Attributes Management (SUPER_ADMIN only)
    #[Route('/user-types/{id}/attributes', name: 'admin_userplus_user_type_attributes', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
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
    #[IsGranted('ROLE_SUPER_ADMIN')]
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
    #[IsGranted('ROLE_SUPER_ADMIN')]
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
            ->orderBy('u.createdAt', 'DESC');

        // Search filter
        $search = $request->query->get('search');
        if ($search) {
            $queryBuilder
                ->andWhere('LOWER(CONCAT(u.firstName, \' \', u.lastName)) LIKE :search OR LOWER(u.email) LIKE :search')
                ->setParameter('search', '%' . strtolower($search) . '%');
        }

        // User type filter
        $userTypeFilter = $request->query->get('user_type');
        if ($userTypeFilter === 'none') {
            $queryBuilder->andWhere('u.userType IS NULL');
        } elseif ($userTypeFilter) {
            $queryBuilder
                ->andWhere('u.userType = :userType')
                ->setParameter('userType', $userTypeFilter);
        }

        // Status filter
        $statusFilter = $request->query->get('status');
        if ($statusFilter === 'active') {
            $queryBuilder->andWhere('u.active = true');
        } elseif ($statusFilter === 'inactive') {
            $queryBuilder->andWhere('u.active = false');
        }

        // Count total for pagination (with same filters)
        $countQueryBuilder = clone $queryBuilder;
        $totalUsers = $countQueryBuilder
            ->select('COUNT(u.id)')
            ->setFirstResult(null)
            ->setMaxResults(null)
            ->getQuery()
            ->getSingleScalarResult();

        // Apply pagination to main query
        $queryBuilder
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        $users = $queryBuilder->getQuery()->getResult();
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
        try {
            if (!$this->moduleManager->isModuleActive('userplus')) {
                return new JsonResponse(['success' => false, 'message' => 'UserPlus module is not active'], 404);
            }

            if (!$user->getUserType()) {
                return new JsonResponse(['success' => false, 'message' => 'User has no type assigned'], 400);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false, 
                'message' => 'Error in initial checks: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }

        try {
            // Handle attributes - with multipart/form-data, they come as array directly
            $attributes = [];
            $attributesData = $request->request->all();
            
            // Check if attributes are nested in an 'attributes' key
            if (isset($attributesData['attributes']) && is_array($attributesData['attributes'])) {
                $attributes = $attributesData['attributes'];
            } else {
                // Extract attributes from form data (they have names like "attributes[key]")
                foreach ($attributesData as $fieldName => $fieldValue) {
                    if (preg_match('/^attributes\[(.*?)\]$/', $fieldName, $matches)) {
                        $attributes[$matches[1]] = $fieldValue;
                    }
                }
            }
            
            // Handle file uploads
            $fileUploads = $request->files->get('file_uploads', []);
            
            $errors = [];
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false, 
                'message' => 'Error processing request data: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }

        try {
            foreach ($user->getUserType()->getAttributes() as $typeAttribute) {
                $key = $typeAttribute->getAttributeKey();
                $value = $attributes[$key] ?? null;
                
                // Trim string values to remove whitespace
                if (is_string($value)) {
                    $value = trim($value);
                }

                // Handle file uploads for file type attributes
                if ($typeAttribute->getAttributeType() === 'file') {
                    $uploadedFile = $fileUploads[$key] ?? null;
                    
                    if ($uploadedFile instanceof UploadedFile) {
                        // Validate file
                        $attributeErrors = $this->validateFileUpload($uploadedFile, $typeAttribute);
                        if (!empty($attributeErrors)) {
                            $errors[$key] = $attributeErrors;
                            continue;
                        }
                        
                        // Save file
                        try {
                            $value = $this->saveUploadedFile($uploadedFile, $user->getId(), $key);
                        } catch (FileException $e) {
                            $errors[$key] = ['File upload failed: ' . $e->getMessage()];
                            continue;
                        }
                    } elseif (($value === null || $value === '') && $typeAttribute->isRequired()) {
                        $errors[$key] = [$typeAttribute->getDisplayName() . ' is required'];
                        continue;
                    }
                    // If no new file uploaded, keep existing value
                } else {
                    // For non-file attributes, validate normally but with improved required check
                    if ($typeAttribute->isRequired() && ($value === null || $value === '')) {
                        $errors[$key] = [$typeAttribute->getDisplayName() . ' is required'];
                        continue;
                    }
                    
                    // Only validate other rules if value is not empty
                    if ($value !== null && $value !== '') {
                        // Special validation for file type
                        if ($typeAttribute->getAttributeType() === 'file') {
                            // Check if file exists using project directory
                            if (!filter_var($value, FILTER_VALIDATE_URL)) {
                                $filePath = $this->getParameter('kernel.project_dir') . '/public' . $value;
                                if (!file_exists($filePath)) {
                                    $errors[$key] = [sprintf('%s file does not exist', $typeAttribute->getDisplayName())];
                                    continue;
                                }
                            }
                        }
                        
                        $attributeErrors = $typeAttribute->validateValue($value);
                        if (!empty($attributeErrors)) {
                            $errors[$key] = $attributeErrors;
                            continue;
                        }
                    }
                }

                // Save the attribute (save empty string for clearing values)
                if ($value !== null) {
                    $user->setUserAttributeValue($key, $value, $typeAttribute->getAttributeType());
                }
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false, 
                'message' => 'Error processing attributes: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }

        if (!empty($errors)) {
            return new JsonResponse([
                'success' => false, 
                'message' => 'Validation errors', 
                'errors' => $errors
            ], 400);
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

    #[Route('/users/create', name: 'admin_userplus_create_user', methods: ['POST'])]
    public function createUser(Request $request): JsonResponse
    {
        if (!$this->moduleManager->isModuleActive('userplus')) {
            return new JsonResponse(['success' => false, 'message' => 'UserPlus module is not active'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['email']) || !isset($data['first_name']) || !isset($data['last_name']) || !isset($data['password'])) {
            return new JsonResponse(['success' => false, 'message' => 'Missing required fields'], 400);
        }

        // Check if email already exists
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $data['email']]);
        if ($existingUser) {
            return new JsonResponse(['success' => false, 'message' => 'Email already exists'], 400);
        }

        $user = new User();
        $user->setEmail($data['email']);
        $user->setFirstName($data['first_name']);
        $user->setLastName($data['last_name']);
        $user->setPassword($this->passwordHasher->hashPassword($user, $data['password']));
        $user->setActive($data['active'] ?? true);

        // Set roles
        $role = $data['roles'] ?? 'ROLE_USER';
        $user->setRoles([$role]);

        // Assign user type if provided
        if (!empty($data['user_type_id'])) {
            $userType = $this->entityManager->getRepository(UserType::class)->find($data['user_type_id']);
            if ($userType) {
                $user->setUserType($userType);
                $user->initializeAttributesFromType();
            }
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return new JsonResponse(['success' => true, 'message' => 'User created successfully']);
    }

    private function validateFileUpload(UploadedFile $file, UserTypeAttribute $typeAttribute): array
    {
        $errors = [];
        
        // Check file size
        if ($typeAttribute->getValidationRules() && isset($typeAttribute->getValidationRules()['max_size'])) {
            $maxSize = $typeAttribute->getValidationRules()['max_size'];
            if ($file->getSize() > $maxSize) {
                $maxSizeMB = round($maxSize / 1024 / 1024, 2);
                $errors[] = sprintf('%s file size must be less than %s MB', $typeAttribute->getDisplayName(), $maxSizeMB);
            }
        }
        
        // Check MIME type
        if ($typeAttribute->getValidationRules() && isset($typeAttribute->getValidationRules()['allowed_mime_types'])) {
            $allowedTypes = $typeAttribute->getValidationRules()['allowed_mime_types'];
            if (!empty($allowedTypes) && !in_array($file->getMimeType(), $allowedTypes)) {
                $errors[] = sprintf('%s file type is not allowed', $typeAttribute->getDisplayName());
            }
        }
        
        return $errors;
    }

    private function saveUploadedFile(UploadedFile $file, int $userId, string $attributeKey): string
    {
        $uploadsDirectory = $this->getParameter('kernel.project_dir') . '/public/uploads/user-attributes';
        
        // Create directory if it doesn't exist
        if (!is_dir($uploadsDirectory)) {
            mkdir($uploadsDirectory, 0755, true);
        }
        
        // Generate a unique filename
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        // Use simple replacement instead of slugger for now
        $safeFilename = preg_replace('/[^A-Za-z0-9\-_]/', '_', $originalFilename);
        $extension = $file->guessExtension();
        $timestamp = time();
        $newFilename = sprintf('%d_%s_%s_%d.%s', $userId, $attributeKey, $safeFilename, $timestamp, $extension);
        
        // Move the file
        $file->move($uploadsDirectory, $newFilename);
        
        // Return the relative path for storage
        return '/uploads/user-attributes/' . $newFilename;
    }
}