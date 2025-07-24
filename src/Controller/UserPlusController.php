<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserAttribute;
use App\Service\ModuleManager;
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
        private ModuleManager $moduleManager
    ) {
    }

    #[Route('', name: 'admin_userplus_list')]
    public function list(): Response
    {
        if (!$this->moduleManager->isModuleActive('userplus')) {
            throw $this->createNotFoundException('UserPlus module is not active');
        }

        $users = $this->entityManager->getRepository(User::class)->findAll();

        return $this->render('admin/userplus/list.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/user/{id}', name: 'admin_userplus_detail', requirements: ['id' => '\d+'])]
    public function detail(User $user): Response
    {
        if (!$this->moduleManager->isModuleActive('userplus')) {
            throw $this->createNotFoundException('UserPlus module is not active');
        }

        $attributes = $this->entityManager->getRepository(UserAttribute::class)
            ->findBy(['user' => $user], ['attributeKey' => 'ASC']);

        return $this->render('admin/userplus/detail.html.twig', [
            'user' => $user,
            'attributes' => $attributes,
        ]);
    }

    #[Route('/user/{id}/attribute', name: 'admin_userplus_add_attribute', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function addAttribute(User $user, Request $request): JsonResponse
    {
        if (!$this->moduleManager->isModuleActive('userplus')) {
            return new JsonResponse(['success' => false, 'message' => 'UserPlus module is not active'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['key']) || !isset($data['value']) || !isset($data['type'])) {
            return new JsonResponse(['success' => false, 'message' => 'Missing required fields'], 400);
        }

        // Vérifier si l'attribut existe déjà
        $existingAttribute = $this->entityManager->getRepository(UserAttribute::class)
            ->findOneBy(['user' => $user, 'attributeKey' => $data['key']]);

        if ($existingAttribute) {
            // Mettre à jour l'attribut existant
            $existingAttribute->setAttributeType($data['type']);
            $existingAttribute->setTypedValue($data['value']);
        } else {
            // Créer un nouvel attribut
            $attribute = new UserAttribute();
            $attribute->setUser($user);
            $attribute->setAttributeKey($data['key']);
            $attribute->setAttributeType($data['type']);
            $attribute->setTypedValue($data['value']);

            $this->entityManager->persist($attribute);
        }

        $this->entityManager->flush();

        return new JsonResponse(['success' => true, 'message' => 'Attribute saved successfully']);
    }

    #[Route('/user/{userId}/attribute/{attributeId}', name: 'admin_userplus_delete_attribute', methods: ['DELETE'], requirements: ['userId' => '\d+', 'attributeId' => '\d+'])]
    public function deleteAttribute(int $userId, int $attributeId): JsonResponse
    {
        if (!$this->moduleManager->isModuleActive('userplus')) {
            return new JsonResponse(['success' => false, 'message' => 'UserPlus module is not active'], 404);
        }

        $attribute = $this->entityManager->getRepository(UserAttribute::class)->find($attributeId);

        if (!$attribute || $attribute->getUser()->getId() !== $userId) {
            return new JsonResponse(['success' => false, 'message' => 'Attribute not found'], 404);
        }

        $this->entityManager->remove($attribute);
        $this->entityManager->flush();

        return new JsonResponse(['success' => true, 'message' => 'Attribute deleted successfully']);
    }

    #[Route('/attributes/search', name: 'admin_userplus_search_attributes')]
    public function searchByAttributes(Request $request): Response
    {
        if (!$this->moduleManager->isModuleActive('userplus')) {
            throw $this->createNotFoundException('UserPlus module is not active');
        }

        $searchKey = $request->query->get('key');
        $searchValue = $request->query->get('value');

        $users = [];
        if ($searchKey) {
            $queryBuilder = $this->entityManager->createQueryBuilder();
            $queryBuilder
                ->select('u')
                ->from(User::class, 'u')
                ->join(UserAttribute::class, 'ua', 'WITH', 'ua.user = u')
                ->where('ua.attributeKey = :key')
                ->setParameter('key', $searchKey);

            if ($searchValue) {
                $queryBuilder
                    ->andWhere('ua.attributeValue LIKE :value')
                    ->setParameter('value', '%' . $searchValue . '%');
            }

            $users = $queryBuilder->getQuery()->getResult();
        }

        // Récupérer toutes les clés d'attributs disponibles
        $attributeKeys = $this->entityManager->createQueryBuilder()
            ->select('DISTINCT ua.attributeKey')
            ->from(UserAttribute::class, 'ua')
            ->orderBy('ua.attributeKey', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return $this->render('admin/userplus/search.html.twig', [
            'users' => $users,
            'attributeKeys' => array_column($attributeKeys, 'attributeKey'),
            'searchKey' => $searchKey,
            'searchValue' => $searchValue,
        ]);
    }
}