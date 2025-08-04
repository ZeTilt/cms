<?php

namespace App\Controller\Admin;

use App\Entity\EntityAttribute;
use App\Service\EavService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/attributes/manage', name: 'admin_attributes_')]
class AttributeController extends AbstractController
{
    public function __construct(
        private EavService $eavService,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/', name: 'index')]
    public function index(): Response
    {
        $entityTypes = ['User', 'Event', 'Gallery']; // Types d'entités supportés
        $statsPerType = [];

        foreach ($entityTypes as $entityType) {
            $statsPerType[$entityType] = [
                'definitions' => $this->eavService->getAttributeDefinitions($entityType),
                'stats' => $this->eavService->getAttributeStats($entityType),
                'usedNames' => $this->eavService->getUsedAttributeNames($entityType)
            ];
        }

        return $this->render('admin/attributes/index.html.twig', [
            'entityTypes' => $entityTypes,
            'statsPerType' => $statsPerType
        ]);
    }

    #[Route('/entity/{entityType}/{entityId}', name: 'manage_entity')]
    public function manageEntity(string $entityType, int $entityId, Request $request): Response
    {
        $attributes = $this->eavService->getEntityAttributeObjects($entityType, $entityId);
        $definitions = $this->eavService->getAttributeDefinitions($entityType);

        if ($request->isMethod('POST')) {
            $data = $request->request->all();

            // Traiter les nouveaux attributs
            if (isset($data['new_attribute'])) {
                $newAttr = $data['new_attribute'];
                if (!empty($newAttr['name']) && !empty($newAttr['value'])) {
                    $this->eavService->setAttribute(
                        $entityType,
                        $entityId,
                        $newAttr['name'],
                        $newAttr['value'],
                        $newAttr['type'] ?? 'text'
                    );

                    $this->addFlash('success', 'Attribut ajouté avec succès');
                    return $this->redirectToRoute('admin_attributes_manage_entity', [
                        'entityType' => $entityType,
                        'entityId' => $entityId
                    ]);
                }
            }

            // Traiter les modifications d'attributs existants
            foreach ($attributes as $attribute) {
                $key = 'attr_' . $attribute->getId();
                if (isset($data[$key])) {
                    $attribute->setTypedValue($data[$key]);
                }
            }

            $this->entityManager->flush();
            $this->addFlash('success', 'Attributs mis à jour avec succès');

            return $this->redirectToRoute('admin_attributes_manage_entity', [
                'entityType' => $entityType,
                'entityId' => $entityId
            ]);
        }

        return $this->render('admin/attributes/manage_entity.html.twig', [
            'entityType' => $entityType,
            'entityId' => $entityId,
            'attributes' => $attributes,
            'definitions' => $definitions
        ]);
    }

    #[Route('/delete/{id}', name: 'delete_attribute', methods: ['POST'])]
    public function deleteAttribute(int $id, Request $request): Response
    {
        $attribute = $this->entityManager->getRepository(EntityAttribute::class)->find($id);

        if (!$attribute) {
            throw $this->createNotFoundException('Attribut non trouvé');
        }

        $entityType = $attribute->getEntityType();
        $entityId = $attribute->getEntityId();

        $this->eavService->removeAttribute($entityType, $entityId, $attribute->getAttributeName());

        $this->addFlash('success', 'Attribut supprimé avec succès');

        return $this->redirectToRoute('admin_attributes_manage_entity', [
            'entityType' => $entityType,
            'entityId' => $entityId
        ]);
    }

    #[Route('/bulk-delete/{entityType}/{entityId}', name: 'bulk_delete', methods: ['POST'])]
    public function bulkDelete(string $entityType, int $entityId): Response
    {
        $deletedCount = $this->eavService->removeEntityAttributes($entityType, $entityId);

        $this->addFlash('success', sprintf('%d attributs supprimés', $deletedCount));

        return $this->redirectToRoute('admin_attributes_manage_entity', [
            'entityType' => $entityType,
            'entityId' => $entityId
        ]);
    }

    #[Route('/search', name: 'search')]
    public function search(Request $request): Response
    {
        $results = [];

        if ($request->isMethod('POST')) {
            $entityType = $request->request->get('entity_type');
            $attributeName = $request->request->get('attribute_name');
            $attributeValue = $request->request->get('attribute_value');

            if ($entityType && $attributeName && $attributeValue) {
                $entityIds = $this->eavService->findEntitiesByAttribute($entityType, $attributeName, $attributeValue);
                $results = [
                    'entityType' => $entityType,
                    'attributeName' => $attributeName,
                    'attributeValue' => $attributeValue,
                    'entityIds' => $entityIds
                ];
            }
        }

        return $this->render('admin/attributes/search.html.twig', [
            'results' => $results
        ]);
    }
}
