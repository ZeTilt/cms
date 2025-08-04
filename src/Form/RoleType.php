<?php

namespace App\Form;

use App\Entity\Permission;
use App\Entity\Role;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class RoleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom technique',
                'help' => 'Format: ROLE_NOM_ROLE (ex: ROLE_MODERATEUR)',
                'attr' => [
                    'class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm',
                    'placeholder' => 'ROLE_MODERATEUR'
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Regex([
                        'pattern' => '/^ROLE_[A-Z_]+$/',
                        'message' => 'Le nom doit commencer par ROLE_ et ne contenir que des majuscules et underscores'
                    ])
                ]
            ])
            ->add('displayName', TextType::class, [
                'label' => 'Nom d\'affichage',
                'help' => 'Nom affiché dans l\'interface utilisateur',
                'attr' => [
                    'class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm',
                    'placeholder' => 'Modérateur'
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['min' => 2, 'max' => 100])
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'help' => 'Description du rôle et de ses responsabilités',
                'attr' => [
                    'class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm',
                    'rows' => 3,
                    'placeholder' => 'Description des responsabilités de ce rôle...'
                ]
            ])
            ->add('hierarchy', IntegerType::class, [
                'label' => 'Niveau hiérarchique',
                'help' => 'Niveau de 0 à 100 (plus élevé = plus de privilèges)',
                'attr' => [
                    'class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm',
                    'min' => 0,
                    'max' => 100,
                    'placeholder' => '50'
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Range(['min' => 0, 'max' => 100])
                ]
            ])
            ->add('active', CheckboxType::class, [
                'label' => 'Rôle actif',
                'required' => false,
                'help' => 'Décochez pour désactiver temporairement ce rôle',
                'attr' => [
                    'class' => 'h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded'
                ]
            ])
            ->add('permissions', EntityType::class, [
                'class' => Permission::class,
                'choice_label' => 'displayName',
                'multiple' => true,
                'expanded' => true,
                'label' => 'Permissions',
                'help' => 'Sélectionnez les permissions accordées à ce rôle',
                'required' => false,
                'query_builder' => function(\Doctrine\ORM\EntityRepository $repository) {
                    return $repository->createQueryBuilder('p')
                        ->where('p.active = :active')
                        ->setParameter('active', true)
                        ->orderBy('p.module', 'ASC')
                        ->addOrderBy('p.action', 'ASC');
                },
                'group_by' => function(Permission $permission) {
                    $moduleNames = [
                        'admin' => 'Administration',
                        'user' => 'Utilisateurs',
                        'role' => 'Rôles',
                        'content' => 'Pages/Articles',
                        'gallery' => 'Galeries',
                        'event' => 'Événements',
                        'system' => 'Système'
                    ];
                    return $moduleNames[$permission->getModule()] ?? ucfirst($permission->getModule());
                },
                'attr' => [
                    'class' => 'permissions-grid'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Role::class,
        ]);
    }
}