<?php

namespace App\Controller;

use App\Entity\EntityAttribute;
use App\Repository\AttributeDefinitionRepository;
use App\Repository\EntityAttributeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user/attributes', name: 'user_attributes_')]
#[IsGranted('ROLE_USER')]
class UserAttributeController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AttributeDefinitionRepository $definitionRepository,
        private EntityAttributeRepository $attributeRepository
    ) {}

    #[Route('', name: 'index', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $user = $this->getUser();
        
        if ($request->isMethod('POST')) {
            $attributeName = $request->request->get('attribute_name');
            $attributeValue = $request->request->get('attribute_value');

            if ($attributeName && $attributeValue !== null) {
                $definition = $this->definitionRepository->findByNameAndEntityType($attributeName, 'App\\Entity\\User');
                
                if (!$definition || !$definition->isActive()) {
                    $this->addFlash('error', 'Attribut non trouvé ou inactif');
                    return $this->redirectToRoute('user_attributes_index');
                }

                $existingAttribute = $this->attributeRepository->findOneByEntityAttribute(
                    'App\\Entity\\User',
                    $user->getId(),
                    $attributeName
                );

                if ($existingAttribute) {
                    $existingAttribute->setAttributeValue($attributeValue)
                        ->setUpdatedAt(new \DateTimeImmutable());
                } else {
                    $attribute = new EntityAttribute();
                    $attribute->setEntityType('App\\Entity\\User')
                        ->setEntityId($user->getId())
                        ->setAttributeName($attributeName)
                        ->setAttributeValue($attributeValue)
                        ->setAttributeType($definition->getFieldType());

                    $this->entityManager->persist($attribute);
                }

                $this->entityManager->flush();
                $this->addFlash('success', 'Attribut mis à jour avec succès');
                
                return $this->redirectToRoute('user_attributes_index');
            }
        }

        $definitions = $this->definitionRepository->findActiveByEntityType('App\\Entity\\User');
        $currentAttributes = $this->attributeRepository->findByEntity('App\\Entity\\User', $user->getId());

        $organizedAttributes = [];
        foreach ($currentAttributes as $attribute) {
            $organizedAttributes[$attribute->getAttributeName()] = $attribute;
        }

        return $this->render('user/attributes/index.html.twig', [
            'user' => $user,
            'definitions' => $definitions,
            'currentAttributes' => $organizedAttributes,
        ]);
    }

    #[Route('/delete/{id}', name: 'delete', methods: ['POST'])]
    public function delete(int $id, Request $request): Response
    {
        $user = $this->getUser();
        $attribute = $this->attributeRepository->find($id);
        
        if (!$attribute || $attribute->getEntityType() !== 'App\\Entity\\User' || $attribute->getEntityId() !== $user->getId()) {
            throw $this->createNotFoundException('Attribut non trouvé');
        }

        if ($this->isCsrfTokenValid('delete_user_attribute_' . $id, $request->request->get('_token'))) {
            $this->entityManager->remove($attribute);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Attribut supprimé avec succès');
        }

        return $this->redirectToRoute('user_attributes_index');
    }
}