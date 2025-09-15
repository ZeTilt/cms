<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\EntityAttribute;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users')]
#[IsGranted('ROLE_SUPER_ADMIN')]
class AdminUserController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    #[Route('', name: 'admin_users_list')]
    public function index(): Response
    {
        $users = $this->userRepository->findAll();
        
        // Récupérer les niveaux de plongée pour tous les utilisateurs
        $divingLevels = [];
        if (!empty($users)) {
            $userIds = array_map(fn($user) => $user->getId(), $users);
            $attributes = $this->entityManager->getRepository(EntityAttribute::class)
                ->createQueryBuilder('a')
                ->where('a.entityType = :entityType')
                ->andWhere('a.entityId IN (:userIds)')
                ->andWhere('a.attributeName = :attributeName')
                ->setParameter('entityType', 'User')
                ->setParameter('userIds', $userIds)
                ->setParameter('attributeName', 'diving_level')
                ->getQuery()
                ->getResult();
                
            foreach ($attributes as $attr) {
                $divingLevels[$attr->getEntityId()] = $this->formatDivingLevel($attr->getAttributeValue());
            }
        }
        
        // Ajouter les niveaux de plongée aux utilisateurs
        foreach ($users as $user) {
            $user->divingLevel = $divingLevels[$user->getId()] ?? null;
        }
        
        return $this->render('admin/users/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/new', name: 'admin_users_new')]
    public function new(Request $request): Response
    {
        $user = new User();
        
        if ($request->isMethod('POST')) {
            $user->setEmail($request->request->get('email'));
            $user->setFirstName($request->request->get('firstName') ?: '');
            $user->setLastName($request->request->get('lastName') ?: '');
            
            $roles = $request->request->all('roles') ?: [];
            $user->setRoles($roles);
            
            $plainPassword = $request->request->get('password');
            if ($plainPassword) {
                $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }
            
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            
            // Sauvegarder le niveau de plongée via EAV
            $this->saveDivingLevel($user, $request->request->get('diving_level'));
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Utilisateur créé avec succès !');
            
            return $this->redirectToRoute('admin_users_list');
        }
        
        return $this->render('admin/users/edit.html.twig', [
            'user' => $user,
            'isNew' => true,
            'user_diving_level' => null,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_users_edit')]
    public function edit(User $user, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $user->setEmail($request->request->get('email'));
            $user->setFirstName($request->request->get('firstName') ?: '');
            $user->setLastName($request->request->get('lastName') ?: '');
            
            $roles = $request->request->all('roles') ?: [];
            $user->setRoles($roles);
            
            $plainPassword = $request->request->get('password');
            if ($plainPassword) {
                $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }
            
            // Sauvegarder le niveau de plongée via EAV
            $this->saveDivingLevel($user, $request->request->get('diving_level'));
            
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Utilisateur mis à jour avec succès !');
            
            return $this->redirectToRoute('admin_users_list');
        }
        
        // Récupérer le niveau de plongée actuel
        $divingLevelAttr = $this->entityManager->getRepository(EntityAttribute::class)
            ->findOneBy([
                'entityType' => 'User',
                'entityId' => $user->getId(),
                'attributeName' => 'diving_level'
            ]);
        
        return $this->render('admin/users/edit.html.twig', [
            'user' => $user,
            'isNew' => false,
            'user_diving_level' => $divingLevelAttr?->getAttributeValue(),
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_users_delete')]
    public function delete(User $user): Response
    {
        // Empêcher la suppression de son propre compte
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
            return $this->redirectToRoute('admin_users_list');
        }
        
        $this->entityManager->remove($user);
        $this->entityManager->flush();
        
        $this->addFlash('success', 'Utilisateur supprimé avec succès !');
        
        return $this->redirectToRoute('admin_users_list');
    }

    private function saveDivingLevel(User $user, ?string $divingLevel): void
    {
        // Chercher l'attribut existant
        $attribute = $this->entityManager->getRepository(EntityAttribute::class)
            ->findOneBy([
                'entityType' => 'User',
                'entityId' => $user->getId(),
                'attributeName' => 'diving_level'
            ]);

        if ($divingLevel) {
            if (!$attribute) {
                // Créer un nouvel attribut
                $attribute = new EntityAttribute();
                $attribute->setEntityType('User');
                $attribute->setEntityId($user->getId());
                $attribute->setAttributeName('diving_level');
                $attribute->setAttributeType('text');
                $this->entityManager->persist($attribute);
            }
            $attribute->setAttributeValue($divingLevel);
        } elseif ($attribute) {
            // Supprimer l'attribut si pas de valeur
            $this->entityManager->remove($attribute);
        }
    }

    private function formatDivingLevel(?string $level): ?string
    {
        if (!$level) return null;
        
        $levels = [
            'bapteme' => 'Baptême',
            'pe12' => 'PE12',
            'pe20' => 'PE20',
            'pe40' => 'PE40',
            'pe60' => 'PE60',
            'pa12' => 'PA12',
            'pa20' => 'PA20',
            'pa40' => 'PA40',
            'pa60' => 'PA60',
            'n1' => 'N1',
            'n2' => 'N2',
            'n3' => 'N3',
            'n4' => 'N4',
            'mf1' => 'MF1',
            'mf2' => 'MF2',
            'e1' => 'E1',
            'e2' => 'E2',
            'e3' => 'E3',
            'e4' => 'E4',
        ];
        
        return $levels[$level] ?? $level;
    }
}