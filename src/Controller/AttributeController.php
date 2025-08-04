<?php

namespace App\Controller;

use App\Entity\AttributeDefinition;
use App\Service\AttributeDefinitionManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/attributes')]
#[IsGranted('ROLE_ADMIN')]
class AttributeController extends AbstractController
{
    public function __construct(
        private AttributeDefinitionManager $definitionManager
    ) {
    }

    #[Route('', name: 'admin_attributes_index')]
    public function index(): Response
    {
        $supportedEntities = $this->definitionManager->getSupportedEntityTypes();
        $supportedTypes = $this->definitionManager->getSupportedAttributeTypes();
        
        return $this->render('admin/attributes/index.html.twig', [
            'supportedEntities' => $supportedEntities,
            'supportedTypes' => $supportedTypes,
        ]);
    }

    #[Route('/entity/{entityType}', name: 'admin_attributes_entity')]
    public function entityAttributes(string $entityType): Response
    {
        $supportedEntities = $this->definitionManager->getSupportedEntityTypes();
        
        if (!array_key_exists($entityType, $supportedEntities)) {
            throw $this->createNotFoundException('Entity type not supported');
        }

        $definitions = $this->definitionManager->getDefinitionsForEntity($entityType);
        $supportedTypes = $this->definitionManager->getSupportedAttributeTypes();

        return $this->render('admin/attributes/entity.html.twig', [
            'entityType' => $entityType,
            'entityLabel' => $supportedEntities[$entityType],
            'definitions' => $definitions,
            'supportedTypes' => $supportedTypes,
        ]);
    }

    #[Route('/entity/{entityType}/create', name: 'admin_attributes_create', methods: ['POST'])]
    public function create(string $entityType, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            if (!$data) {
                return new JsonResponse(['success' => false, 'message' => 'Invalid JSON data'], 400);
            }

            $definition = $this->definitionManager->createDefinition(
                $entityType,
                $data['attribute_name'],
                $data['display_name'],
                $data['attribute_type'] ?? 'text',
                $data['required'] ?? false,
                $data['default_value'] ?? null,
                $data['description'] ?? null,
                $data['options'] ?? null,
                $data['validation_rules'] ?? null,
                $data['display_order'] ?? 0
            );

            $this->definitionManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Attribute definition created successfully',
                'definition' => [
                    'id' => $definition->getId(),
                    'attribute_name' => $definition->getAttributeName(),
                    'display_name' => $definition->getDisplayName(),
                    'attribute_type' => $definition->getAttributeType(),
                    'required' => $definition->isRequired(),
                    'default_value' => $definition->getDefaultValue(),
                    'description' => $definition->getDescription(),
                    'display_order' => $definition->getDisplayOrder(),
                ]
            ]);

        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'An error occurred'], 500);
        }
    }

    #[Route('/definition/{id}/update', name: 'admin_attributes_update', methods: ['PUT'])]
    public function update(AttributeDefinition $definition, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            if (!$data) {
                return new JsonResponse(['success' => false, 'message' => 'Invalid JSON data'], 400);
            }

            if (isset($data['display_name'])) {
                $definition->setDisplayName($data['display_name']);
            }
            if (isset($data['attribute_type'])) {
                $definition->setAttributeType($data['attribute_type']);
            }
            if (isset($data['required'])) {
                $definition->setRequired($data['required']);
            }
            if (isset($data['default_value'])) {
                $definition->setDefaultValue($data['default_value']);
            }
            if (isset($data['description'])) {
                $definition->setDescription($data['description']);
            }
            if (isset($data['options'])) {
                $definition->setOptions($data['options']);
            }
            if (isset($data['validation_rules'])) {
                $definition->setValidationRules($data['validation_rules']);
            }
            if (isset($data['display_order'])) {
                $definition->setDisplayOrder($data['display_order']);
            }

            $this->definitionManager->updateDefinition($definition);
            $this->definitionManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Attribute definition updated successfully'
            ]);

        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'An error occurred'], 500);
        }
    }

    #[Route('/definition/{id}/delete', name: 'admin_attributes_delete', methods: ['DELETE'])]
    public function delete(AttributeDefinition $definition): JsonResponse
    {
        try {
            $this->definitionManager->deleteDefinition($definition);
            $this->definitionManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Attribute definition deleted successfully'
            ]);

        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'An error occurred'], 500);
        }
    }

    #[Route('/types', name: 'admin_attributes_types', methods: ['GET'])]
    public function getAttributeTypes(): JsonResponse
    {
        return new JsonResponse([
            'types' => $this->definitionManager->getSupportedAttributeTypes()
        ]);
    }

    #[Route('/entity/{entityType}/attributes', name: 'admin_attributes_for_entity', methods: ['GET'])]
    public function getAttributesForEntity(string $entityType): JsonResponse
    {
        $supportedEntities = $this->definitionManager->getSupportedEntityTypes();
        
        if (!array_key_exists($entityType, $supportedEntities)) {
            return new JsonResponse(['error' => 'Entity type not supported'], 400);
        }

        $attributes = $this->definitionManager->getAvailableAttributesForEntity($entityType);

        return new JsonResponse([
            'attributes' => $attributes,
            'entity_label' => $supportedEntities[$entityType]
        ]);
    }

    #[Route('/definition/{id}', name: 'admin_attributes_get_definition', methods: ['GET'])]
    public function getDefinition(AttributeDefinition $definition): JsonResponse
    {
        return new JsonResponse([
            'success' => true,
            'definition' => [
                'id' => $definition->getId(),
                'entity_type' => $definition->getEntityType(),
                'attribute_name' => $definition->getAttributeName(),
                'display_name' => $definition->getDisplayName(),
                'attribute_type' => $definition->getAttributeType(),
                'required' => $definition->isRequired(),
                'default_value' => $definition->getDefaultValue(),
                'description' => $definition->getDescription(),
                'options' => $definition->getOptions(),
                'validation_rules' => $definition->getValidationRules(),
                'display_order' => $definition->getDisplayOrder(),
                'active' => $definition->isActive()
            ]
        ]);
    }
}