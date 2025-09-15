<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\EntityAttribute;
use App\Entity\DivingLevel;
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
                
            // Récupérer aussi les niveaux de plongée pour le formatage
            $divingLevelEntities = $this->entityManager->getRepository(DivingLevel::class)->findAll();
            $levelMap = [];
            foreach ($divingLevelEntities as $level) {
                $levelMap[$level->getCode()] = $level->getName();
            }
            
            foreach ($attributes as $attr) {
                $code = $attr->getAttributeValue();
                $divingLevels[$attr->getEntityId()] = $levelMap[$code] ?? $code;
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
            $user->setFirstName($request->request->get('firstName') ?: null);
            $user->setLastName($request->request->get('lastName') ?: null);
            
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
            
            // Sauvegarder le statut apnéiste via EAV
            $this->saveFreediverStatus($user, (bool) $request->request->get('is_freediver'));
            
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Utilisateur créé avec succès !');
            
            return $this->redirectToRoute('admin_users_list');
        }
        
        // Récupérer les niveaux de plongée actifs
        $divingLevels = $this->entityManager->getRepository(DivingLevel::class)
            ->findBy(['isActive' => true], ['sortOrder' => 'ASC', 'name' => 'ASC']);
        
        return $this->render('admin/users/edit.html.twig', [
            'user' => $user,
            'isNew' => true,
            'user_diving_level' => null,
            'user_is_freediver' => false,
            'diving_levels' => $divingLevels,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_users_edit')]
    public function edit(User $user, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $user->setEmail($request->request->get('email'));
            $user->setFirstName($request->request->get('firstName') ?: null);
            $user->setLastName($request->request->get('lastName') ?: null);
            
            $roles = $request->request->all('roles') ?: [];
            $user->setRoles($roles);
            
            $plainPassword = $request->request->get('password');
            if ($plainPassword) {
                $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }
            
            // Sauvegarder le niveau de plongée via EAV
            $this->saveDivingLevel($user, $request->request->get('diving_level'));
            
            // Sauvegarder le statut apnéiste via EAV
            $this->saveFreediverStatus($user, (bool) $request->request->get('is_freediver'));
            
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
            
        // Récupérer le statut apnéiste actuel
        $freediverAttr = $this->entityManager->getRepository(EntityAttribute::class)
            ->findOneBy([
                'entityType' => 'User',
                'entityId' => $user->getId(),
                'attributeName' => 'is_freediver'
            ]);
        
        // Récupérer les niveaux de plongée actifs
        $divingLevels = $this->entityManager->getRepository(DivingLevel::class)
            ->findBy(['isActive' => true], ['sortOrder' => 'ASC', 'name' => 'ASC']);
        
        return $this->render('admin/users/edit.html.twig', [
            'user' => $user,
            'isNew' => false,
            'user_diving_level' => $divingLevelAttr?->getAttributeValue(),
            'user_is_freediver' => (bool) ($freediverAttr?->getAttributeValue() ?? false),
            'diving_levels' => $divingLevels,
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

    private function saveFreediverStatus(User $user, bool $isFreediver): void
    {
        // Chercher l'attribut existant
        $attribute = $this->entityManager->getRepository(EntityAttribute::class)
            ->findOneBy([
                'entityType' => 'User',
                'entityId' => $user->getId(),
                'attributeName' => 'is_freediver'
            ]);

        if ($isFreediver) {
            if (!$attribute) {
                // Créer un nouvel attribut
                $attribute = new EntityAttribute();
                $attribute->setEntityType('User');
                $attribute->setEntityId($user->getId());
                $attribute->setAttributeName('is_freediver');
                $attribute->setAttributeType('boolean');
                $this->entityManager->persist($attribute);
            }
            $attribute->setAttributeValue('1');
        } elseif ($attribute) {
            // Supprimer l'attribut si pas apnéiste
            $this->entityManager->remove($attribute);
        }
    }
}