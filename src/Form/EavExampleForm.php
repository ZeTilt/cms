<?php

namespace App\Form;

use App\Service\EavFormBuilder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Example form demonstrating how to integrate EAV fields into Symfony forms
 */
class EavExampleForm extends AbstractType
{
    public function __construct(
        private EavFormBuilder $eavFormBuilder
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Add regular form fields first if needed
        // $builder->add('name', TextType::class, [...]);
        
        // Add EAV fields based on entity type
        $this->eavFormBuilder->addEavFields(
            $builder, 
            $options['entity_type'], 
            $options['entity'] ?? null
        );

        // Add submit button
        $builder->add('submit', SubmitType::class, [
            'label' => 'Enregistrer',
            'attr' => ['class' => 'btn btn-primary']
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'entity_type' => null,
            'entity' => null,
        ]);

        $resolver->setRequired(['entity_type']);
        $resolver->setAllowedTypes('entity_type', 'string');
        $resolver->setAllowedTypes('entity', ['object', 'null']);
    }
}