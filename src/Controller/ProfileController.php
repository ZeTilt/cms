<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\EventRegistration;
use App\Entity\AttributeDefinition;
use App\Service\EavService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/profile')]
#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TranslatorInterface $translator,
        private EavService $eavService
    ) {
    }

    #[Route('', name: 'profile_show')]
    public function show(): Response
    {
        $user = $this->getUser();
        
        // Get user's event registrations
        $registrations = $this->entityManager->getRepository(EventRegistration::class)
            ->createQueryBuilder('r')
            ->join('r.event', 'e')
            ->where('r.user = :user')
            ->setParameter('user', $user)
            ->orderBy('e.startDate', 'DESC')
            ->getQuery()
            ->getResult();

        // Separate upcoming and past registrations
        $upcomingRegistrations = [];
        $pastRegistrations = [];
        $now = new \DateTimeImmutable();

        foreach ($registrations as $registration) {
            $event = $registration->getEvent();
            if ($event->getStartDate() && $event->getStartDate() > $now) {
                $upcomingRegistrations[] = $registration;
            } else {
                $pastRegistrations[] = $registration;
            }
        }

        // Get user EAV attributes with definitions
        $attributeDefinitions = $this->entityManager->getRepository(AttributeDefinition::class)
            ->findBy(['entityType' => 'User', 'active' => true], ['displayOrder' => 'ASC']);
        
        $userAttributes = $this->eavService->getEntityAttributesRaw('User', $user->getId());

        return $this->render('profile/show.html.twig', [
            'user' => $user,
            'upcomingRegistrations' => $upcomingRegistrations,
            'pastRegistrations' => $pastRegistrations,
            'attributeDefinitions' => $attributeDefinitions,
            'userAttributes' => $userAttributes,
        ]);
    }

    #[Route('/edit', name: 'profile_edit')]
    public function edit(Request $request): Response
    {
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            // Update basic profile information
            $firstName = $request->request->get('first_name');
            $lastName = $request->request->get('last_name');
            $email = $request->request->get('email');

            if ($firstName) {
                $user->setFirstName($firstName);
            }
            if ($lastName) {
                $user->setLastName($lastName);
            }
            if ($email && $email !== $user->getEmail()) {
                // Check if email is already used
                $existingUser = $this->entityManager->getRepository(User::class)
                    ->findOneBy(['email' => $email]);
                
                if ($existingUser && $existingUser !== $user) {
                    $this->addFlash('error', $this->translator->trans('messages.email_already_used', [], 'profile'));
                    return $this->redirectToRoute('profile_edit');
                }
                
                $user->setEmail($email);
            }

            // Handle user EAV attributes
            $attributes = $request->request->all('attributes') ?? [];
            $fileUploads = $request->files->get('file_uploads', []);
            
            // Get all attribute definitions to process both regular and file attributes
            $attributeDefinitions = $this->entityManager->getRepository(AttributeDefinition::class)
                ->findBy(['entityType' => 'User', 'active' => true]);
            
            foreach ($attributeDefinitions as $definition) {
                $key = $definition->getAttributeName();
                $value = $attributes[$key] ?? null;
                
                if ($definition->getAttributeType() === 'file') {
                    // Handle file upload
                    $uploadedFile = $fileUploads[$key] ?? null;
                    
                    if ($uploadedFile && $uploadedFile->isValid()) {
                        // Upload new file
                        try {
                            $filePath = $this->handleFileUpload($uploadedFile, $user->getId(), $key, $definition);
                            $this->eavService->setAttribute('User', $user->getId(), $key, $filePath, 'file');
                        } catch (\Exception $e) {
                            $this->addFlash('error', 'Error uploading file: ' . $e->getMessage());
                        }
                    }
                    // If no new file uploaded, keep existing file (do nothing)
                } else {
                    // Handle regular attributes
                    if (!empty($value) || $value === '0') {
                        $this->eavService->setAttribute('User', $user->getId(), $key, $value, $definition->getAttributeType());
                    } else {
                        // Remove attribute if value is empty
                        $this->eavService->removeAttribute('User', $user->getId(), $key);
                    }
                }
            }

            $this->entityManager->flush();

            $this->addFlash('success', $this->translator->trans('messages.profile_updated', [], 'profile'));
            return $this->redirectToRoute('profile_show');
        }

        // Get available attribute definitions for User
        $attributeDefinitions = $this->entityManager->getRepository(AttributeDefinition::class)
            ->findBy(['entityType' => 'User', 'active' => true], ['displayOrder' => 'ASC']);
        
        // Get current user attributes
        $userAttributes = $this->eavService->getEntityAttributesRaw('User', $user->getId());

        return $this->render('profile/edit.html.twig', [
            'user' => $user,
            'attributeDefinitions' => $attributeDefinitions,
            'userAttributes' => $userAttributes,
        ]);
    }

    private function handleFileUpload(UploadedFile $file, int $userId, string $attributeKey, AttributeDefinition $definition): string
    {
        // Validate MIME type if restrictions are configured
        $options = $definition->getOptions();
        if ($options && isset($options['allowed_mime_types']) && !empty($options['allowed_mime_types'])) {
            $fileMimeType = $file->getMimeType();
            if (!in_array($fileMimeType, $options['allowed_mime_types'])) {
                throw new \InvalidArgumentException(sprintf(
                    'File type %s is not allowed. Allowed types: %s',
                    $fileMimeType,
                    implode(', ', $options['allowed_mime_types'])
                ));
            }
        }
        
        // Validate file size if restrictions are configured
        if ($options && isset($options['max_file_size'])) {
            $maxSizeBytes = $options['max_file_size'] * 1024 * 1024; // Convert MB to bytes
            if ($file->getSize() > $maxSizeBytes) {
                throw new \InvalidArgumentException(sprintf(
                    'File size %.2f MB exceeds maximum allowed size of %d MB',
                    $file->getSize() / (1024 * 1024),
                    $options['max_file_size']
                ));
            }
        }
        
        $uploadsDirectory = $this->getParameter('kernel.project_dir') . '/public/uploads/user-attributes';
        
        // Create directory if it doesn't exist
        if (!is_dir($uploadsDirectory)) {
            mkdir($uploadsDirectory, 0755, true);
        }
        
        // Generate a unique filename
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
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