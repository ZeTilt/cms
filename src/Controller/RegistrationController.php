<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\DivingLevel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class RegistrationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    #[Route('/inscription', name: 'app_register')]
    public function register(Request $request): Response
    {
        // Récupérer les niveaux de plongée actifs
        $divingLevels = $this->entityManager->getRepository(DivingLevel::class)
            ->findBy(['isActive' => true], ['sortOrder' => 'ASC', 'name' => 'ASC']);

        if ($request->isMethod('POST')) {
            // Vérifier que l'email n'existe pas déjà
            $existingUser = $this->entityManager->getRepository(User::class)
                ->findOneBy(['email' => $request->request->get('email')]);

            if ($existingUser) {
                $this->addFlash('error', 'Un compte avec cet email existe déjà.');
                return $this->redirectToRoute('app_register');
            }

            // Créer le nouvel utilisateur
            $user = new User();
            $user->setEmail($request->request->get('email'));
            $user->setFirstName($request->request->get('firstName'));
            $user->setLastName($request->request->get('lastName'));
            $user->setStatus('pending'); // En attente de validation
            $user->setActive(false); // Inactif jusqu'à validation

            // Hasher le mot de passe
            $plainPassword = $request->request->get('password');
            if ($plainPassword) {
                $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            // Sauvegarder les attributs EAV si fournis
            if ($request->request->get('diving_level')) {
                $this->saveDivingLevel($user, $request->request->get('diving_level'));
            }
            
            if ($request->request->get('is_freediver')) {
                $this->saveFreediverStatus($user, true);
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Votre inscription a été envoyée ! Un administrateur validera votre compte sous peu.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'diving_levels' => $divingLevels,
        ]);
    }

    private function saveDivingLevel(User $user, ?string $divingLevel): void
    {
        if (!$divingLevel) return;

        $attribute = new \App\Entity\EntityAttribute();
        $attribute->setEntityType('User');
        $attribute->setEntityId($user->getId());
        $attribute->setAttributeName('diving_level');
        $attribute->setAttributeType('text');
        $attribute->setAttributeValue($divingLevel);
        
        $this->entityManager->persist($attribute);
    }

    private function saveFreediverStatus(User $user, bool $isFreediver): void
    {
        if (!$isFreediver) return;

        $attribute = new \App\Entity\EntityAttribute();
        $attribute->setEntityType('User');
        $attribute->setEntityId($user->getId());
        $attribute->setAttributeName('is_freediver');
        $attribute->setAttributeType('boolean');
        $attribute->setAttributeValue('1');
        
        $this->entityManager->persist($attribute);
    }
}