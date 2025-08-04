<?php

namespace App\Twig;

use App\Service\AttributeDefinitionManager;
use App\Service\AttributeManager;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class EavExtension extends AbstractExtension
{
    public function __construct(
        private AttributeManager $attributeManager,
        private AttributeDefinitionManager $attributeDefinitionManager
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('eav_get', [$this, 'getAttributeValue']),
            new TwigFunction('eav_has', [$this, 'hasAttribute']),
            new TwigFunction('eav_display', [$this, 'displayAttributeValue'], ['is_safe' => ['html']]),
            new TwigFunction('eav_definitions', [$this, 'getDefinitionsForEntity']),
            new TwigFunction('eav_all', [$this, 'getAllAttributesForEntity']),
        ];
    }

    /**
     * Get attribute value for an entity
     */
    public function getAttributeValue(string $entityType, int $entityId, string $attributeName, mixed $default = null): mixed
    {
        $attribute = $this->attributeManager->getAttribute($entityType, $entityId, $attributeName);
        
        if (!$attribute) {
            return $default;
        }

        $definition = $this->attributeDefinitionManager->getDefinition($entityType, $attributeName);
        if (!$definition) {
            return $attribute->getAttributeValue();
        }

        return $this->formatValueForDisplay($attribute->getAttributeValue(), $definition->getAttributeType());
    }

    /**
     * Check if an entity has a specific attribute
     */
    public function hasAttribute(string $entityType, int $entityId, string $attributeName): bool
    {
        return $this->attributeManager->getAttribute($entityType, $entityId, $attributeName) !== null;
    }

    /**
     * Display attribute value with proper formatting
     */
    public function displayAttributeValue(string $entityType, int $entityId, string $attributeName, array $options = []): string
    {
        $attribute = $this->attributeManager->getAttribute($entityType, $entityId, $attributeName);
        
        if (!$attribute) {
            return $options['empty'] ?? '';
        }

        $definition = $this->attributeDefinitionManager->getDefinition($entityType, $attributeName);
        if (!$definition) {
            return htmlspecialchars($attribute->getAttributeValue());
        }

        return $this->formatValueForDisplay($attribute->getAttributeValue(), $definition->getAttributeType(), $options);
    }

    /**
     * Get all attribute definitions for an entity type
     */
    public function getDefinitionsForEntity(string $entityType): array
    {
        return $this->attributeDefinitionManager->getDefinitionsForEntity($entityType);
    }

    /**
     * Get all attributes for an entity
     */
    public function getAllAttributesForEntity(string $entityType, int $entityId): array
    {
        return $this->attributeManager->getAttributesForEntity($entityType, $entityId);
    }

    /**
     * Format value for display based on attribute type
     */
    private function formatValueForDisplay(string $value, string $attributeType, array $options = []): string
    {
        if (empty($value)) {
            return $options['empty'] ?? '';
        }

        switch ($attributeType) {
            case 'boolean':
                $boolValue = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                return $options['boolean_format'] ?? ($boolValue ? 'Oui' : 'Non');

            case 'date':
                try {
                    $date = new \DateTime($value);
                    $format = $options['date_format'] ?? 'd/m/Y';
                    return $date->format($format);
                } catch (\Exception $e) {
                    return htmlspecialchars($value);
                }

            case 'number':
                if (is_numeric($value)) {
                    $decimals = $options['decimals'] ?? (strpos($value, '.') !== false ? 2 : 0);
                    return number_format((float) $value, $decimals, ',', ' ');
                }
                return htmlspecialchars($value);

            case 'json':
                try {
                    $data = json_decode($value, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        return $options['json_format'] ?? '<pre>' . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>';
                    }
                } catch (\Exception $e) {
                    // Fall through to default
                }
                return htmlspecialchars($value);

            case 'file':
                if ($options['as_link'] ?? false) {
                    $label = $options['link_label'] ?? basename($value);
                    return sprintf('<a href="%s" target="_blank" class="text-blue-600 hover:text-blue-800">%s</a>', 
                        htmlspecialchars($value), 
                        htmlspecialchars($label)
                    );
                }
                return htmlspecialchars($value);

            case 'select':
                // For select fields, we might want to show the label instead of the value
                // This would require storing the definition options and looking up the label
                return htmlspecialchars($value);

            case 'textarea':
                if ($options['preserve_newlines'] ?? true) {
                    return nl2br(htmlspecialchars($value));
                }
                return htmlspecialchars($value);

            default:
                return htmlspecialchars($value);
        }
    }
}