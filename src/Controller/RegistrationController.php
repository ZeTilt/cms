<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserType;
use App\Service\EavService;
use App\Service\ModuleManager;
use App\Service\UserTypeManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/registration')]
class RegistrationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ModuleManager $moduleManager,
        private UserTypeManager $userTypeManager,
        private UserPasswordHasherInterface $passwordHasher,
        private ValidatorInterface $validator,
        private EavService $eavService
    ) {
    }

    #[Route('', name: 'registration_form')]
    public function register(Request $request): Response
    {
        if (!$this->moduleManager->isModuleActive('registration')) {
            throw $this->createNotFoundException('Registration module is not active');
        }

        $config = $this->moduleManager->getModuleConfig('registration');

        // Check if registration is enabled
        if (!($config['enabled'] ?? true)) {
            return $this->render('registration/disabled.html.twig');
        }

        $errors = [];
        $formData = [];

        if ($request->isMethod('POST')) {
            $formData = [
                'email' => $request->request->get('email'),
                'firstName' => $request->request->get('first_name'),
                'lastName' => $request->request->get('last_name'),
                'password' => $request->request->get('password'),
                'confirmPassword' => $request->request->get('confirm_password'),
                'userTypeId' => $request->request->get('user_type_id'),
                'termsAccepted' => $request->request->getBoolean('terms_accepted')
            ];

            // Validate form data
            $errors = $this->validateRegistrationForm($formData, $config);

            if (empty($errors)) {
                try {
                    $user = $this->createUser($formData, $config);

                    if ($config['require_approval'] ?? false) {
                        $this->addFlash('success', 'Your registration has been submitted and is pending approval. You will receive an email notification once approved.');
                    } elseif ($config['email_verification'] ?? false) {
                        $this->addFlash('success', 'Registration successful! Please check your email to verify your account.');
                    } else {
                        $this->addFlash('success', 'Registration successful! You can now log in.');
                    }

                    return $this->redirectToRoute('app_login');
                } catch (\Exception $e) {
                    $errors['general'] = ['An error occurred during registration. Please try again.'];
                }
            }
        }

        // Get available user types for registration
        $availableUserTypes = $this->getAvailableUserTypes($config);

        return $this->render('registration/register.html.twig', [
            'errors' => $errors,
            'formData' => $formData,
            'userTypes' => $availableUserTypes,
            'config' => $config,
        ]);
    }

    #[Route('/admin', name: 'admin_registration_dashboard')]
    #[IsGranted('ROLE_ADMIN')]
    public function adminDashboard(): Response
    {
        if (!$this->moduleManager->isModuleActive('registration')) {
            throw $this->createNotFoundException('Registration module is not active');
        }

        $config = $this->moduleManager->getModuleConfig('registration');
        $pendingUsers = [];

        if ($config['require_approval'] ?? false) {
            // Utiliser EAV pour trouver les utilisateurs avec statut pending
            $pendingUserIds = $this->eavService->findEntitiesByAttribute(
                'User', 
                'registration_status', 
                'pending'
            );
            
            if (!empty($pendingUserIds)) {
                $pendingUsers = $this->entityManager->getRepository(User::class)
                    ->createQueryBuilder('u')
                    ->where('u.id IN (:ids)')
                    ->andWhere('u.active = false')
                    ->setParameter('ids', $pendingUserIds)
                    ->orderBy('u.createdAt', 'DESC')
                    ->getQuery()
                    ->getResult();
            } else {
                // Fallback vers la recherche JSON pour les données non migrées
                $pendingUsers = $this->entityManager->getRepository(User::class)
                    ->createQueryBuilder('u')
                    ->where('u.active = false')
                    ->leftJoin('u.userAttributes', 'ua')
                    ->andWhere('ua.attributeKey = :statusKey AND ua.attributeValue LIKE :status')
                    ->setParameter('statusKey', 'registration_status')
                    ->setParameter('status', '%"registration_status":"pending"%')
                    ->orderBy('u.createdAt', 'DESC')
                    ->getQuery()
                    ->getResult();
            }
        }

        $recentRegistrations = $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('u.createdAt >= :since')
            ->setParameter('since', new \DateTime('-30 days'))
            ->orderBy('u.createdAt', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        return $this->render('admin/registration/dashboard.html.twig', [
            'config' => $config,
            'pendingUsers' => $pendingUsers,
            'recentRegistrations' => $recentRegistrations,
        ]);
    }

    #[Route('/admin/settings', name: 'admin_registration_settings')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function adminSettings(Request $request): Response
    {
        if (!$this->moduleManager->isModuleActive('registration')) {
            throw $this->createNotFoundException('Registration module is not active');
        }

        $config = $this->moduleManager->getModuleConfig('registration');

        if ($request->isMethod('POST')) {
            $newConfig = [
                'enabled' => $request->request->getBoolean('enabled', true),
                'require_approval' => $request->request->getBoolean('require_approval', false),
                'email_verification' => $request->request->getBoolean('email_verification', false),
                'default_role' => $request->request->get('default_role', 'ROLE_USER'),
                'allowed_user_types' => $request->request->all('allowed_user_types') ?: [],
                'default_user_type' => $request->request->get('default_user_type'),
                'welcome_message' => $request->request->get('welcome_message', ''),
                'terms_required' => $request->request->getBoolean('terms_required', false),
                'terms_url' => $request->request->get('terms_url', ''),
            ];

            $this->moduleManager->updateModuleConfig('registration', $newConfig);
            $this->addFlash('success', 'Registration settings updated successfully!');

            return $this->redirectToRoute('admin_registration_settings');
        }

        $userTypes = $this->userTypeManager->getAllUserTypes();

        return $this->render('admin/registration/settings.html.twig', [
            'config' => $config,
            'userTypes' => $userTypes,
        ]);
    }

    #[Route('/admin/approve/{id}', name: 'admin_registration_approve', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function approveUser(User $user): Response
    {
        if (!$this->moduleManager->isModuleActive('registration')) {
            throw $this->createNotFoundException('Registration module is not active');
        }

        $registrationStatus = $user->getDynamicAttribute('registration_status');
        if ($registrationStatus !== 'pending') {
            $this->addFlash('error', 'User is not pending approval.');
            return $this->redirectToRoute('admin_registration_dashboard');
        }

        $user->setActive(true);
        $user->setDynamicAttribute('registration_status', 'approved');
        $user->setDynamicAttribute('approved_by', (string)$this->getUser()->getId());
        $user->setDynamicAttribute('approved_at', (new \DateTime())->format('Y-m-d H:i:s'));

        $this->entityManager->flush();

        // TODO: Send approval email notification

        $this->addFlash('success', sprintf('User %s has been approved successfully!', $user->getFullName()));
        return $this->redirectToRoute('admin_registration_dashboard');
    }

    #[Route('/admin/reject/{id}', name: 'admin_registration_reject', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function rejectUser(User $user, Request $request): Response
    {
        if (!$this->moduleManager->isModuleActive('registration')) {
            throw $this->createNotFoundException('Registration module is not active');
        }

        $registrationStatus = $user->getDynamicAttribute('registration_status');
        if ($registrationStatus !== 'pending') {
            $this->addFlash('error', 'User is not pending approval.');
            return $this->redirectToRoute('admin_registration_dashboard');
        }

        $rejectionReason = $request->request->get('rejection_reason', '');

        $user->setDynamicAttribute('registration_status', 'rejected');
        $user->setDynamicAttribute('rejected_by', (string)$this->getUser()->getId());
        $user->setDynamicAttribute('rejected_at', (new \DateTime())->format('Y-m-d H:i:s'));
        $user->setDynamicAttribute('rejection_reason', $rejectionReason);

        $this->entityManager->flush();

        // TODO: Send rejection email notification

        $this->addFlash('success', sprintf('User %s has been rejected.', $user->getFullName()));
        return $this->redirectToRoute('admin_registration_dashboard');
    }

    private function validateRegistrationForm(array $data, array $config): array
    {
        $errors = [];

        // Email validation
        if (empty($data['email'])) {
            $errors['email'] = ['Email is required.'];
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = ['Please enter a valid email address.'];
        } else {
            // Check if email already exists
            $existingUser = $this->entityManager->getRepository(User::class)
                ->findOneBy(['email' => $data['email']]);
            if ($existingUser) {
                $errors['email'] = ['This email address is already registered.'];
            }
        }

        // Name validation
        if (empty($data['firstName'])) {
            $errors['firstName'] = ['First name is required.'];
        }
        if (empty($data['lastName'])) {
            $errors['lastName'] = ['Last name is required.'];
        }

        // Password validation
        if (empty($data['password'])) {
            $errors['password'] = ['Password is required.'];
        } elseif (strlen($data['password']) < 8) {
            $errors['password'] = ['Password must be at least 8 characters long.'];
        }

        // Confirm password validation
        if ($data['password'] !== $data['confirmPassword']) {
            $errors['confirmPassword'] = ['Passwords do not match.'];
        }

        // User type validation
        if (!empty($data['userTypeId'])) {
            $userType = $this->entityManager->getRepository(UserType::class)
                ->find($data['userTypeId']);
            if (!$userType) {
                $errors['userTypeId'] = ['Invalid user type selected.'];
            }
        }

        // Terms acceptance validation
        if (($config['terms_required'] ?? false) && !$data['termsAccepted']) {
            $errors['termsAccepted'] = ['You must accept the terms and conditions.'];
        }

        return $errors;
    }

    private function createUser(array $data, array $config): User
    {
        $user = new User();
        $user->setEmail($data['email']);
        $user->setFirstName($data['firstName']);
        $user->setLastName($data['lastName']);
        $user->setPassword($this->passwordHasher->hashPassword($user, $data['password']));
        $user->setRoles([$config['default_role'] ?? 'ROLE_USER']);

        // Set user type if provided
        if (!empty($data['userTypeId'])) {
            $userType = $this->entityManager->getRepository(UserType::class)
                ->find($data['userTypeId']);
            if ($userType) {
                $user->setUserType($userType);
                $user->initializeAttributesFromType();
            }
        } elseif (!empty($config['default_user_type'])) {
            $userType = $this->entityManager->getRepository(UserType::class)
                ->find($config['default_user_type']);
            if ($userType) {
                $user->setUserType($userType);
                $user->initializeAttributesFromType();
            }
        }

        // Set registration attributes
        $user->setDynamicAttribute('registration_date', (new \DateTime())->format('Y-m-d H:i:s'));
        $user->setDynamicAttribute('registration_ip', $_SERVER['REMOTE_ADDR'] ?? 'unknown');

        if ($config['require_approval'] ?? false) {
            $user->setActive(false);
            $user->setStatus('pending_approval');
            $user->setDynamicAttribute('registration_status', 'pending'); // Garde pour compatibilité
        } elseif ($config['email_verification'] ?? false) {
            $user->setActive(false);
            $user->setStatus('pending_approval'); // Email verification sera géré séparément
            $user->setDynamicAttribute('registration_status', 'pending_verification');
            $user->setDynamicAttribute('verification_token', bin2hex(random_bytes(32)));
        } else {
            $user->setActive(true);
            $user->setStatus('approved');
            $user->setDynamicAttribute('registration_status', 'active'); // Garde pour compatibilité
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function getAvailableUserTypes(array $config): array
    {
        $allowedTypes = $config['allowed_user_types'] ?? [];

        if (empty($allowedTypes)) {
            return $this->userTypeManager->getAllUserTypes();
        }

        return $this->entityManager->getRepository(UserType::class)
            ->createQueryBuilder('ut')
            ->where('ut.id IN (:allowedTypes)')
            ->setParameter('allowedTypes', $allowedTypes)
            ->orderBy('ut.displayName', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
