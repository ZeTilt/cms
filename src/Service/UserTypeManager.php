<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\UserType;
use App\Entity\UserTypeAttribute;
use App\Entity\UserAttribute;
use Doctrine\ORM\EntityManagerInterface;

class UserTypeManager
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Get all user types
     */
    public function getAllUserTypes(): array
    {
        return $this->entityManager->getRepository(UserType::class)
            ->findBy([], ['displayName' => 'ASC']);
    }

    /**
     * Get active user types
     */
    public function getActiveUserTypes(): array
    {
        return $this->entityManager->getRepository(UserType::class)
            ->findBy(['active' => true], ['displayName' => 'ASC']);
    }

    /**
     * Get user type by name
     */
    public function getUserTypeByName(string $name): ?UserType
    {
        return $this->entityManager->getRepository(UserType::class)
            ->findOneBy(['name' => $name]);
    }

    /**
     * Create a new user type
     */
    public function createUserType(string $name, string $displayName, ?string $description = null): UserType
    {
        $userType = new UserType();
        $userType->setName($name);
        $userType->setDisplayName($displayName);
        $userType->setDescription($description);
        $userType->setActive(true);

        $this->entityManager->persist($userType);
        $this->entityManager->flush();

        return $userType;
    }

    /**
     * Add attribute template to user type
     */
    public function addAttributeToUserType(
        UserType $userType,
        string $key,
        string $displayName,
        string $type = 'text',
        bool $required = false,
        ?string $defaultValue = null,
        ?string $description = null,
        ?array $validationRules = null,
        ?array $options = null,
        int $displayOrder = 0
    ): UserTypeAttribute {
        $attribute = new UserTypeAttribute();
        $attribute->setUserType($userType);
        $attribute->setAttributeKey($key);
        $attribute->setDisplayName($displayName);
        $attribute->setAttributeType($type);
        $attribute->setRequired($required);
        $attribute->setDefaultValue($defaultValue);
        $attribute->setDescription($description);
        $attribute->setValidationRules($validationRules);
        $attribute->setOptions($options);
        $attribute->setDisplayOrder($displayOrder);

        $this->entityManager->persist($attribute);
        $this->entityManager->flush();

        return $attribute;
    }

    /**
     * Assign user type to user and initialize attributes
     */
    public function assignUserType(User $user, UserType $userType): void
    {
        $user->setUserType($userType);
        $user->initializeAttributesFromType();

        $this->entityManager->flush();
    }

    /**
     * Initialize default user types and their attributes
     */
    public function initializeDefaultUserTypes(): void
    {
        // Client type
        $clientType = $this->getUserTypeByName('client');
        if (!$clientType) {
            $clientType = $this->createUserType(
                'client',
                'Client',
                'Standard client user with basic contact information'
            );

            // Client attributes
            $this->addAttributeToUserType(
                $clientType,
                'phone',
                'Phone Number',
                'text',
                true,
                null,
                'Primary contact phone number',
                ['pattern' => '/^[\+]?[0-9\-\(\)\s]+$/'],
                null,
                1
            );

            $this->addAttributeToUserType(
                $clientType,
                'address',
                'Address',
                'textarea',
                false,
                null,
                'Full postal address',
                ['max_length' => 500],
                null,
                2
            );

            $this->addAttributeToUserType(
                $clientType,
                'preferred_contact_method',
                'Preferred Contact Method',
                'select',
                false,
                'email',
                'How would you prefer to be contacted?',
                null,
                [
                    ['value' => 'email', 'label' => 'Email'],
                    ['value' => 'phone', 'label' => 'Phone'],
                    ['value' => 'sms', 'label' => 'SMS']
                ],
                3
            );
        }

        // Photographer type
        $photographerType = $this->getUserTypeByName('photographer');
        if (!$photographerType) {
            $photographerType = $this->createUserType(
                'photographer',
                'Photographer',
                'Professional photographer with portfolio and specialties'
            );

            // Photographer attributes
            $this->addAttributeToUserType(
                $photographerType,
                'portfolio_url',
                'Portfolio URL',
                'text',
                true,
                null,
                'Link to your online portfolio',
                ['pattern' => '/^https?:\/\/.+/'],
                null,
                1
            );

            $this->addAttributeToUserType(
                $photographerType,
                'specialty',
                'Photography Specialty',
                'select',
                true,
                null,
                'Your main area of expertise',
                null,
                [
                    ['value' => 'wedding', 'label' => 'Wedding Photography'],
                    ['value' => 'portrait', 'label' => 'Portrait Photography'],
                    ['value' => 'landscape', 'label' => 'Landscape Photography'],
                    ['value' => 'commercial', 'label' => 'Commercial Photography'],
                    ['value' => 'event', 'label' => 'Event Photography'],
                    ['value' => 'fashion', 'label' => 'Fashion Photography']
                ],
                2
            );

            $this->addAttributeToUserType(
                $photographerType,
                'years_experience',
                'Years of Experience',
                'number',
                true,
                null,
                'How many years have you been photographing professionally?',
                ['min' => 0, 'max' => 50],
                null,
                3
            );

            $this->addAttributeToUserType(
                $photographerType,
                'equipment_brands',
                'Equipment Brands',
                'text',
                false,
                null,
                'Main camera and lens brands you use (comma-separated)',
                ['max_length' => 200],
                null,
                4
            );

            $this->addAttributeToUserType(
                $photographerType,
                'hourly_rate',
                'Hourly Rate (€)',
                'number',
                false,
                null,
                'Your standard hourly rate in euros',
                ['min' => 0, 'max' => 1000],
                null,
                5
            );
        }

        // Vendor type
        $vendorType = $this->getUserTypeByName('vendor');
        if (!$vendorType) {
            $vendorType = $this->createUserType(
                'vendor',
                'Vendor',
                'Service provider or vendor with business information'
            );

            // Vendor attributes
            $this->addAttributeToUserType(
                $vendorType,
                'business_name',
                'Business Name',
                'text',
                true,
                null,
                'Official name of your business',
                ['min_length' => 2, 'max_length' => 100],
                null,
                1
            );

            $this->addAttributeToUserType(
                $vendorType,
                'business_type',
                'Business Type',
                'select',
                true,
                null,
                'Type of business or service you provide',
                null,
                [
                    ['value' => 'catering', 'label' => 'Catering'],
                    ['value' => 'decoration', 'label' => 'Decoration'],
                    ['value' => 'music', 'label' => 'Music/DJ'],
                    ['value' => 'venue', 'label' => 'Venue'],
                    ['value' => 'flowers', 'label' => 'Flowers'],
                    ['value' => 'transport', 'label' => 'Transportation'],
                    ['value' => 'other', 'label' => 'Other']
                ],
                2
            );

            $this->addAttributeToUserType(
                $vendorType,
                'website',
                'Website',
                'text',
                false,
                null,
                'Your business website',
                ['pattern' => '/^https?:\/\/.+/'],
                null,
                3
            );

            $this->addAttributeToUserType(
                $vendorType,
                'license_number',
                'Business License Number',
                'text',
                false,
                null,
                'Official business registration number',
                ['max_length' => 50],
                null,
                4
            );

            $this->addAttributeToUserType(
                $vendorType,
                'service_area',
                'Service Area',
                'textarea',
                true,
                null,
                'Geographic areas where you provide services',
                ['max_length' => 300],
                null,
                5
            );
        }
    }

    /**
     * Get user type statistics
     */
    public function getUserTypeStatistics(): array
    {
        $stats = [];
        $userTypes = $this->getAllUserTypes();

        foreach ($userTypes as $userType) {
            $stats[] = [
                'userType' => $userType,
                'totalUsers' => $userType->getUserCount(),
                'activeUsers' => $this->entityManager->getRepository(User::class)
                    ->createQueryBuilder('u')
                    ->select('COUNT(u.id)')
                    ->where('u.userType = :userType')
                    ->andWhere('u.active = :active')
                    ->setParameter('userType', $userType)
                    ->setParameter('active', true)
                    ->getQuery()
                    ->getSingleScalarResult(),
                'totalAttributes' => $userType->getAttributes()->count(),
                'requiredAttributes' => $userType->getRequiredAttributes()->count(),
            ];
        }

        return $stats;
    }

    /**
     * Validate user attributes against user type requirements
     */
    public function validateUserAttributes(User $user): array
    {
        $errors = [];

        if (!$user->getUserType()) {
            return $errors;
        }

        foreach ($user->getUserType()->getAttributes() as $typeAttribute) {
            $userAttribute = $user->getUserAttributeByKey($typeAttribute->getAttributeKey());
            $value = $userAttribute ? $userAttribute->getAttributeValue() : null;

            $attributeErrors = $typeAttribute->validateValue($value);
            if (!empty($attributeErrors)) {
                $errors[$typeAttribute->getAttributeKey()] = $attributeErrors;
            }
        }

        return $errors;
    }

    /**
     * Sync user attributes with user type template
     * Adds missing attributes and removes obsolete ones
     */
    public function syncUserAttributesWithType(User $user): void
    {
        if (!$user->getUserType()) {
            return;
        }

        // Add missing attributes
        $user->initializeAttributesFromType();

        // Remove attributes that are no longer in the user type template
        $typeAttributeKeys = [];
        foreach ($user->getUserType()->getAttributes() as $typeAttribute) {
            $typeAttributeKeys[] = $typeAttribute->getAttributeKey();
        }

        foreach ($user->getUserAttributes() as $userAttribute) {
            if (!in_array($userAttribute->getAttributeKey(), $typeAttributeKeys)) {
                $user->removeUserAttribute($userAttribute);
                $this->entityManager->remove($userAttribute);
            }
        }

        $this->entityManager->flush();
    }
}