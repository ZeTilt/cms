<?php

namespace App\Service;

use App\Entity\AttributeDefinition;
use App\Entity\EntityAttribute;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class EavFormBuilder
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AttributeDefinitionManager $attributeDefinitionManager,
        private AttributeManager $attributeManager
    ) {
    }

    /**
     * Add EAV fields to a form based on entity type
     */
    public function addEavFields(FormBuilderInterface $builder, string $entityType, ?object $entity = null): void
    {
        $definitions = $this->attributeDefinitionManager->getDefinitionsForEntity($entityType);
        
        if (empty($definitions)) {
            return;
        }

        // Load existing attribute values if entity exists
        $existingValues = [];
        if ($entity && method_exists($entity, 'getId') && $entity->getId()) {
            $existingValues = $this->attributeManager->getAttributesForEntity($entityType, $entity->getId());
        }

        foreach ($definitions as $definition) {
            $this->addFieldForDefinition($builder, $definition, $existingValues);
        }
    }

    /**
     * Add a single field based on attribute definition
     */
    private function addFieldForDefinition(FormBuilderInterface $builder, AttributeDefinition $definition, array $existingValues): void
    {
        $fieldName = 'eav_' . $definition->getAttributeName();
        $options = $this->buildFieldOptions($definition, $existingValues);
        $fieldType = $this->getFormTypeForAttributeType($definition->getAttributeType());

        $builder->add($fieldName, $fieldType, $options);
    }

    /**
     * Build form field options based on attribute definition
     */
    private function buildFieldOptions(AttributeDefinition $definition, array $existingValues): array
    {
        $options = [
            'label' => $definition->getDisplayName(),
            'required' => $definition->isRequired(),
            'help' => $definition->getDescription(),
            'attr' => [
                'data-attribute-name' => $definition->getAttributeName(),
                'data-attribute-type' => $definition->getAttributeType()
            ]
        ];

        // Set default value or existing value
        $existingValue = $existingValues[$definition->getAttributeName()] ?? null;
        if ($existingValue) {
            $options['data'] = $this->formatValueForForm($existingValue->getAttributeValue(), $definition->getAttributeType());
        } elseif ($definition->getDefaultValue()) {
            $options['data'] = $this->formatValueForForm($definition->getDefaultValue(), $definition->getAttributeType());
        }

        // Add type-specific options
        switch ($definition->getAttributeType()) {
            case 'select':
                $options['choices'] = $this->buildSelectChoices($definition);
                $options['placeholder'] = 'Sélectionner...';
                break;

            case 'textarea':
                $options['attr']['rows'] = 4;
                break;

            case 'number':
                $options['attr']['step'] = 'any';
                break;

            case 'date':
                $options['widget'] = 'single_text';
                $options['html5'] = true;
                break;

            case 'file':
                $options['required'] = false; // Files are typically optional for updates
                $options['attr']['accept'] = $this->getAcceptedFileTypes($definition);
                break;

            case 'boolean':
                $options['required'] = false; // Checkboxes are typically optional
                break;
        }

        // Add validation constraints
        $constraints = $this->buildValidationConstraints($definition);
        if (!empty($constraints)) {
            $options['constraints'] = $constraints;
        }

        return $options;
    }

    /**
     * Get Symfony form type class for attribute type
     */
    private function getFormTypeForAttributeType(string $attributeType): string
    {
        return match ($attributeType) {
            'text' => TextType::class,
            'textarea' => TextareaType::class,
            'number' => NumberType::class,
            'boolean' => CheckboxType::class,
            'date' => DateType::class,
            'select' => ChoiceType::class,
            'file' => FileType::class,
            default => TextType::class
        };
    }

    /**
     * Build choices array for select fields
     */
    private function buildSelectChoices(AttributeDefinition $definition): array
    {
        $options = $definition->getOptions();
        if (!$options || !isset($options['choices'])) {
            return [];
        }

        $choices = [];
        foreach ($options['choices'] as $choice) {
            if (is_array($choice) && isset($choice['label'], $choice['value'])) {
                $choices[$choice['label']] = $choice['value'];
            } else {
                // Simple string choices
                $choices[$choice] = $choice;
            }
        }

        return $choices;
    }

    /**
     * Build validation constraints based on attribute definition
     */
    private function buildValidationConstraints(AttributeDefinition $definition): array
    {
        $constraints = [];
        
        if ($definition->isRequired()) {
            $constraints[] = new Assert\NotBlank([
                'message' => 'Ce champ est obligatoire.'
            ]);
        }

        $validationRules = $definition->getValidationRules();
        if (!$validationRules) {
            return $constraints;
        }

        switch ($definition->getAttributeType()) {
            case 'text':
            case 'textarea':
                if (isset($validationRules['min_length'])) {
                    $constraints[] = new Assert\Length([
                        'min' => $validationRules['min_length'],
                        'minMessage' => "Ce champ doit contenir au moins {{ limit }} caractères."
                    ]);
                }
                if (isset($validationRules['max_length'])) {
                    $constraints[] = new Assert\Length([
                        'max' => $validationRules['max_length'],
                        'maxMessage' => "Ce champ ne peut pas contenir plus de {{ limit }} caractères."
                    ]);
                }
                if (isset($validationRules['pattern'])) {
                    $constraints[] = new Assert\Regex([
                        'pattern' => $validationRules['pattern'],
                        'message' => 'Le format de ce champ n\'est pas valide.'
                    ]);
                }
                break;

            case 'number':
                if (isset($validationRules['min_value'])) {
                    $constraints[] = new Assert\GreaterThanOrEqual([
                        'value' => $validationRules['min_value'],
                        'message' => "La valeur doit être supérieure ou égale à {{ compared_value }}."
                    ]);
                }
                if (isset($validationRules['max_value'])) {
                    $constraints[] = new Assert\LessThanOrEqual([
                        'value' => $validationRules['max_value'],
                        'message' => "La valeur doit être inférieure ou égale à {{ compared_value }}."
                    ]);
                }
                break;

            case 'date':
                if (isset($validationRules['min_date'])) {
                    $constraints[] = new Assert\GreaterThanOrEqual([
                        'value' => new \DateTime($validationRules['min_date']),
                        'message' => "La date doit être postérieure au {{ compared_value|date('d/m/Y') }}."
                    ]);
                }
                if (isset($validationRules['max_date'])) {
                    $constraints[] = new Assert\LessThanOrEqual([
                        'value' => new \DateTime($validationRules['max_date']),
                        'message' => "La date doit être antérieure au {{ compared_value|date('d/m/Y') }}."
                    ]);
                }
                break;

            case 'file':
                if (isset($validationRules['max_size'])) {
                    $constraints[] = new Assert\File([
                        'maxSize' => $validationRules['max_size'],
                        'maxSizeMessage' => 'Le fichier est trop volumineux ({{ size }} {{ suffix }}). La taille maximale autorisée est {{ limit }} {{ suffix }}.'
                    ]);
                }
                if (isset($validationRules['allowed_extensions'])) {
                    $constraints[] = new Assert\File([
                        'extensions' => $validationRules['allowed_extensions'],
                        'extensionsMessage' => 'Veuillez télécharger un fichier valide ({{ extensions }}).'
                    ]);
                }
                break;
        }

        return $constraints;
    }

    /**
     * Format stored value for form display
     */
    private function formatValueForForm(string $value, string $attributeType): mixed
    {
        return match ($attributeType) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'number' => is_numeric($value) ? (float) $value : null,
            'date' => $value ? new \DateTime($value) : null,
            'json' => json_decode($value, true),
            default => $value
        };
    }

    /**
     * Get accepted file types for file inputs
     */
    private function getAcceptedFileTypes(AttributeDefinition $definition): string
    {
        $validationRules = $definition->getValidationRules();
        
        if (!$validationRules || !isset($validationRules['allowed_extensions'])) {
            return ''; // Accept all file types
        }

        $extensions = $validationRules['allowed_extensions'];
        return '.' . implode(',.', $extensions);
    }

    /**
     * Process form data and save EAV attributes
     */
    public function processFormData(array $formData, string $entityType, int $entityId): void
    {
        $eavData = [];
        
        // Extract EAV fields from form data
        foreach ($formData as $key => $value) {
            if (strpos($key, 'eav_') === 0) {
                $attributeName = substr($key, 4); // Remove 'eav_' prefix
                $eavData[$attributeName] = $value;
            }
        }

        // Save attributes
        foreach ($eavData as $attributeName => $value) {
            $definition = $this->attributeDefinitionManager->getDefinition($entityType, $attributeName);
            if (!$definition) {
                continue;
            }

            $formattedValue = $this->formatValueForStorage($value, $definition->getAttributeType());
            
            $this->attributeManager->setAttribute(
                $entityType,
                $entityId,
                $attributeName,
                $formattedValue,
                $definition->getAttributeType()
            );
        }
    }

    /**
     * Format form value for storage
     */
    private function formatValueForStorage(mixed $value, string $attributeType): string
    {
        if ($value === null) {
            return '';
        }

        return match ($attributeType) {
            'boolean' => $value ? '1' : '0',
            'date' => $value instanceof \DateTime ? $value->format('Y-m-d') : (string) $value,
            'json' => json_encode($value),
            'file' => is_string($value) ? $value : '', // File path should be stored as string
            default => (string) $value
        };
    }

    /**
     * Get all EAV values for an entity as an associative array
     */
    public function getEavValuesForEntity(string $entityType, int $entityId): array
    {
        $attributes = $this->attributeManager->getAttributesForEntity($entityType, $entityId);
        $values = [];

        foreach ($attributes as $attributeName => $attribute) {
            $definition = $this->attributeDefinitionManager->getDefinition($entityType, $attributeName);
            if (!$definition) {
                continue;
            }

            $values[$attributeName] = $this->formatValueForForm(
                $attribute->getAttributeValue(),
                $definition->getAttributeType()
            );
        }

        return $values;
    }
}