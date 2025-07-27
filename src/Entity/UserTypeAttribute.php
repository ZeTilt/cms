<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'user_type_attributes')]
#[ORM\UniqueConstraint(name: 'unique_user_type_attribute', columns: ['user_type_id', 'attribute_key'])]
class UserTypeAttribute
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: UserType::class, inversedBy: 'attributes')]
    #[ORM\JoinColumn(nullable: false)]
    private UserType $userType;

    #[ORM\Column(length: 100)]
    private string $attributeKey;

    #[ORM\Column(length: 150)]
    private string $displayName;

    #[ORM\Column(length: 50)]
    private string $attributeType = 'text'; // text, number, boolean, date, json, select, textarea, file

    #[ORM\Column(type: 'boolean')]
    private bool $required = false;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $defaultValue = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $validationRules = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $options = null; // For select type: list of options

    #[ORM\Column]
    private int $displayOrder = 0;

    #[ORM\Column(type: 'boolean')]
    private bool $active = true;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserType(): UserType
    {
        return $this->userType;
    }

    public function setUserType(UserType $userType): static
    {
        $this->userType = $userType;
        return $this;
    }

    public function getAttributeKey(): string
    {
        return $this->attributeKey;
    }

    public function setAttributeKey(string $attributeKey): static
    {
        $this->attributeKey = $attributeKey;
        return $this;
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public function setDisplayName(string $displayName): static
    {
        $this->displayName = $displayName;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getAttributeType(): string
    {
        return $this->attributeType;
    }

    public function setAttributeType(string $attributeType): static
    {
        $this->attributeType = $attributeType;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function setRequired(bool $required): static
    {
        $this->required = $required;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getDefaultValue(): ?string
    {
        return $this->defaultValue;
    }

    public function setDefaultValue(?string $defaultValue): static
    {
        $this->defaultValue = $defaultValue;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getValidationRules(): ?array
    {
        return $this->validationRules;
    }

    public function setValidationRules(?array $validationRules): static
    {
        $this->validationRules = $validationRules;
        return $this;
    }

    public function getOptions(): ?array
    {
        return $this->options;
    }

    public function setOptions(?array $options): static
    {
        $this->options = $options;
        return $this;
    }

    public function getDisplayOrder(): int
    {
        return $this->displayOrder;
    }

    public function setDisplayOrder(int $displayOrder): static
    {
        $this->displayOrder = $displayOrder;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Get HTML input type for forms
     */
    public function getHtmlInputType(): string
    {
        return match ($this->attributeType) {
            'number' => 'number',
            'date' => 'datetime-local',
            'boolean' => 'checkbox',
            'textarea' => 'textarea',
            'select' => 'select',
            'file' => 'file',
            default => 'text',
        };
    }

    /**
     * Get validation attributes for HTML forms
     */
    public function getHtmlValidationAttributes(): array
    {
        $attributes = [];

        if ($this->required) {
            $attributes['required'] = true;
        }

        if ($this->validationRules) {
            if (isset($this->validationRules['min_length'])) {
                $attributes['minlength'] = $this->validationRules['min_length'];
            }
            if (isset($this->validationRules['max_length'])) {
                $attributes['maxlength'] = $this->validationRules['max_length'];
            }
            if (isset($this->validationRules['pattern'])) {
                $attributes['pattern'] = $this->validationRules['pattern'];
            }
            if (isset($this->validationRules['min'])) {
                $attributes['min'] = $this->validationRules['min'];
            }
            if (isset($this->validationRules['max'])) {
                $attributes['max'] = $this->validationRules['max'];
            }
            
            // File-specific attributes
            if ($this->attributeType === 'file') {
                if (isset($this->validationRules['allowed_mime_types']) && is_array($this->validationRules['allowed_mime_types'])) {
                    $attributes['accept'] = implode(',', $this->validationRules['allowed_mime_types']);
                }
            }
        }

        return $attributes;
    }

    /**
     * Validate a value against this attribute's rules
     */
    public function validateValue(mixed $value): array
    {
        $errors = [];

        // Skip required validation here - it's handled in the controller
        // This method only validates format/rules for non-empty values
        
        if ($value !== null && $value !== '' && $this->validationRules) {
            // Type-specific validation
            switch ($this->attributeType) {
                case 'number':
                    if (!is_numeric($value)) {
                        $errors[] = sprintf('%s must be a number', $this->displayName);
                    } else {
                        if (isset($this->validationRules['min']) && $value < $this->validationRules['min']) {
                            $errors[] = sprintf('%s must be at least %s', $this->displayName, $this->validationRules['min']);
                        }
                        if (isset($this->validationRules['max']) && $value > $this->validationRules['max']) {
                            $errors[] = sprintf('%s must be at most %s', $this->displayName, $this->validationRules['max']);
                        }
                    }
                    break;

                case 'text':
                case 'textarea':
                    if (isset($this->validationRules['min_length']) && strlen($value) < $this->validationRules['min_length']) {
                        $errors[] = sprintf('%s must be at least %d characters', $this->displayName, $this->validationRules['min_length']);
                    }
                    if (isset($this->validationRules['max_length']) && strlen($value) > $this->validationRules['max_length']) {
                        $errors[] = sprintf('%s must be at most %d characters', $this->displayName, $this->validationRules['max_length']);
                    }
                    if (isset($this->validationRules['pattern']) && !preg_match($this->validationRules['pattern'], $value)) {
                        $errors[] = sprintf('%s format is invalid', $this->displayName);
                    }
                    break;

                case 'select':
                    if ($this->options && !in_array($value, array_column($this->options, 'value'))) {
                        $errors[] = sprintf('%s has an invalid value', $this->displayName);
                    }
                    break;

                case 'file':
                    // For file type, skip file existence validation in entity
                    // File existence should be validated in the controller where we have access to kernel.project_dir
                    if (is_string($value) && !empty($value)) {
                        // Skip file existence check here - handled in controller
                        
                        // Check allowed mime types if specified
                        if (isset($this->validationRules['allowed_mime_types']) && is_array($this->validationRules['allowed_mime_types'])) {
                            $allowedTypes = $this->validationRules['allowed_mime_types'];
                            if (!empty($allowedTypes)) {
                                $mimeType = null;
                                if (file_exists($value)) {
                                    $mimeType = mime_content_type($value);
                                } elseif (filter_var($value, FILTER_VALIDATE_URL)) {
                                    // For URLs, try to get mime type from extension
                                    $extension = pathinfo(parse_url($value, PHP_URL_PATH), PATHINFO_EXTENSION);
                                    $mimeType = $this->getMimeTypeFromExtension($extension);
                                }
                                
                                if ($mimeType && !in_array($mimeType, $allowedTypes)) {
                                    $errors[] = sprintf('%s file type is not allowed', $this->displayName);
                                }
                            }
                        }
                        
                        // Check file size if specified (for local files)
                        if (isset($this->validationRules['max_size']) && file_exists($value)) {
                            $maxSize = $this->validationRules['max_size']; // in bytes
                            if (filesize($value) > $maxSize) {
                                $maxSizeMB = round($maxSize / 1024 / 1024, 2);
                                $errors[] = sprintf('%s file size must be less than %s MB', $this->displayName, $maxSizeMB);
                            }
                        }
                    }
                    break;
            }
        }

        return $errors;
    }

    /**
     * Get typed value based on attribute type
     */
    public function getTypedValue(string $rawValue): mixed
    {
        return match ($this->attributeType) {
            'boolean' => filter_var($rawValue, FILTER_VALIDATE_BOOLEAN),
            'number' => is_numeric($rawValue) ? (float) $rawValue : null,
            'date' => $rawValue ? new \DateTime($rawValue) : null,
            'json' => $rawValue ? json_decode($rawValue, true) : null,
            default => $rawValue,
        };
    }

    /**
     * Get MIME type from file extension
     */
    private function getMimeTypeFromExtension(string $extension): ?string
    {
        $mimeTypes = [
            // Images
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'bmp' => 'image/bmp',
            'ico' => 'image/x-icon',
            
            // Documents
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'txt' => 'text/plain',
            'rtf' => 'application/rtf',
            'odt' => 'application/vnd.oasis.opendocument.text',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
            
            // Archives
            'zip' => 'application/zip',
            'rar' => 'application/vnd.rar',
            '7z' => 'application/x-7z-compressed',
            'tar' => 'application/x-tar',
            'gz' => 'application/gzip',
            
            // Audio/Video
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'ogg' => 'audio/ogg',
            'mp4' => 'video/mp4',
            'avi' => 'video/x-msvideo',
            'mov' => 'video/quicktime',
            'wmv' => 'video/x-ms-wmv',
            'flv' => 'video/x-flv',
            'webm' => 'video/webm',
        ];
        
        return $mimeTypes[strtolower($extension)] ?? null;
    }

    /**
     * Get available MIME types grouped by category
     */
    public static function getAvailableMimeTypes(): array
    {
        return [
            'Images' => [
                'image/jpeg' => 'JPEG Image (.jpg, .jpeg)',
                'image/png' => 'PNG Image (.png)',
                'image/gif' => 'GIF Image (.gif)',
                'image/webp' => 'WebP Image (.webp)',
                'image/svg+xml' => 'SVG Image (.svg)',
                'image/bmp' => 'BMP Image (.bmp)',
            ],
            'Documents' => [
                'application/pdf' => 'PDF Document (.pdf)',
                'application/msword' => 'Word Document (.doc)',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'Word Document (.docx)',
                'application/vnd.ms-excel' => 'Excel Spreadsheet (.xls)',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'Excel Spreadsheet (.xlsx)',
                'text/plain' => 'Text File (.txt)',
            ],
            'Archives' => [
                'application/zip' => 'ZIP Archive (.zip)',
                'application/vnd.rar' => 'RAR Archive (.rar)',
                'application/x-7z-compressed' => '7-Zip Archive (.7z)',
            ],
            'Audio' => [
                'audio/mpeg' => 'MP3 Audio (.mp3)',
                'audio/wav' => 'WAV Audio (.wav)',
                'audio/ogg' => 'OGG Audio (.ogg)',
            ],
            'Video' => [
                'video/mp4' => 'MP4 Video (.mp4)',
                'video/x-msvideo' => 'AVI Video (.avi)',
                'video/quicktime' => 'QuickTime Video (.mov)',
                'video/webm' => 'WebM Video (.webm)',
            ],
        ];
    }

    public function __toString(): string
    {
        return $this->displayName;
    }
}