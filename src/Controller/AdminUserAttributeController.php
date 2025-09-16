<?php

namespace App\Controller;

use App\Entity\EntityAttribute;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\EntityAttributeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/user-attributes', name: 'admin_user_attributes_')]
class AdminUserAttributeController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private EntityAttributeRepository $attributeRepository
    ) {}

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $users = $this->userRepository->findBy(['status' => 'approved'], ['lastName' => 'ASC']);
        
        return $this->render('admin/user_attributes/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/user/{userId}', name: 'user_attributes', methods: ['GET', 'POST'])]
    public function userAttributes(int $userId, Request $request): Response
    {
        $user = $this->userRepository->find($userId);
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }

        if ($request->isMethod('POST')) {
            $attributeName = $request->request->get('attribute_name');
            $attributeValue = $request->request->get('attribute_value');
            $attributeType = $request->request->get('attribute_type', 'string');

            if ($attributeName && $attributeValue !== null) {
                // Vérifier si l'attribut existe déjà
                $existingAttribute = $this->attributeRepository->findOneBy([
                    'entityType' => 'User',
                    'entityId' => $user->getId(),
                    'attributeName' => $attributeName
                ]);

                if ($existingAttribute) {
                    // Mettre à jour
                    $existingAttribute->setAttributeValue($attributeValue);
                    $existingAttribute->setUpdatedAt(new \DateTime());
                } else {
                    // Créer nouveau
                    $attribute = new EntityAttribute();
                    $attribute->setEntityType('User')
                        ->setEntityId($user->getId())
                        ->setAttributeName($attributeName)
                        ->setAttributeValue($attributeValue)
                        ->setAttributeType($attributeType)
                        ->setCreatedAt(new \DateTime());
                    
                    $this->entityManager->persist($attribute);
                }

                $this->entityManager->flush();
                $this->addFlash('success', 'Attribut mis à jour avec succès');
                
                return $this->redirectToRoute('admin_user_attributes_user_attributes', ['userId' => $userId]);
            }
        }

        // Récupérer tous les attributs de cet utilisateur
        $attributes = $this->attributeRepository->findBy([
            'entityType' => 'User',
            'entityId' => $user->getId()
        ]);

        // Organiser par nom d'attribut
        $organizedAttributes = [];
        foreach ($attributes as $attribute) {
            $organizedAttributes[$attribute->getAttributeName()] = $attribute;
        }

        return $this->render('admin/user_attributes/user_form.html.twig', [
            'user' => $user,
            'attributes' => $organizedAttributes,
            'availableAttributes' => $this->getAvailableAttributes()
        ]);
    }

    #[Route('/attribute/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(int $id, Request $request): Response
    {
        $attribute = $this->attributeRepository->find($id);
        if (!$attribute) {
            throw $this->createNotFoundException('Attribut non trouvé');
        }

        $userId = $attribute->getEntityId();

        if ($this->isCsrfTokenValid('delete_attribute_' . $id, $request->request->get('_token'))) {
            $this->entityManager->remove($attribute);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Attribut supprimé avec succès');
        }

        return $this->redirectToRoute('admin_user_attributes_user_attributes', ['userId' => $userId]);
    }

    private function getAvailableAttributes(): array
    {
        return [
            'diving_level' => [
                'label' => 'Niveau de plongée',
                'type' => 'select',
                'options' => [
                    'N1' => 'N1 - Plongeur Encadré 20m',
                    'N2' => 'N2 - Plongeur Autonome 20m',
                    'N3' => 'N3 - Plongeur Autonome 60m',
                    'N4' => 'N4 - Guide de Palanquée',
                    'N5' => 'N5 - Directeur de Plongée',
                    'E1' => 'E1 - Initiateur',
                    'E2' => 'E2 - Moniteur Fédéral 1er',
                    'E3' => 'E3 - Moniteur Fédéral 2ème',
                    'E4' => 'E4 - Moniteur Fédéral 3ème',
                    'RIFAP' => 'RIFAP'
                ]
            ],
            'birth_date' => [
                'label' => 'Date de naissance',
                'type' => 'date'
            ],
            'medical_certificate_date' => [
                'label' => 'Date certificat médical',
                'type' => 'date'
            ],
            'swimming_test_date' => [
                'label' => 'Date test natation',
                'type' => 'date'
            ],
            'freediver' => [
                'label' => 'Apnéiste',
                'type' => 'select',
                'options' => [
                    '1' => 'Oui',
                    '0' => 'Non'
                ]
            ],
            'emergency_contact_name' => [
                'label' => 'Contact urgence (nom)',
                'type' => 'text'
            ],
            'emergency_contact_phone' => [
                'label' => 'Contact urgence (téléphone)',
                'type' => 'tel'
            ],
            'insurance_number' => [
                'label' => 'N° d\'assurance',
                'type' => 'text'
            ],
            'license_number' => [
                'label' => 'N° de licence FFESSM',
                'type' => 'text'
            ],
            'club_member_since' => [
                'label' => 'Membre du club depuis',
                'type' => 'date'
            ]
        ];
    }
}