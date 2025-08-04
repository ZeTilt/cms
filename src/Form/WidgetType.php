<?php

namespace App\Form;

use App\Entity\Widget;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class WidgetType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'widget.name',
                'help' => 'widget.name_help',
                'attr' => [
                    'class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm',
                    'placeholder' => 'mon-widget-meteo'
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['min' => 2, 'max' => 100]),
                    new Assert\Regex([
                        'pattern' => '/^[a-z0-9-_]+$/',
                        'message' => 'widget.name_pattern'
                    ])
                ]
            ])
            ->add('title', TextType::class, [
                'label' => 'widget.title',
                'help' => 'widget.title_help',
                'attr' => [
                    'class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm',
                    'placeholder' => 'Widget Météo Marine'
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['min' => 2, 'max' => 255])
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'widget.description',
                'help' => 'widget.description_help',
                'required' => false,
                'attr' => [
                    'class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm',
                    'rows' => 3,
                    'placeholder' => 'Description du widget...'
                ]
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'widget.type',
                'help' => 'widget.type_help',
                'choices' => Widget::getAvailableTypes(),
                'attr' => [
                    'class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm'
                ]
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'widget.category',
                'help' => 'widget.category_help',
                'choices' => Widget::getAvailableCategories(),
                'required' => false,
                'placeholder' => 'widget.select_category',
                'attr' => [
                    'class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm'
                ]
            ])
            ->add('content', TextareaType::class, [
                'label' => 'widget.content',
                'help' => 'widget.content_help',
                'attr' => [
                    'class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-mono',
                    'rows' => 10,
                    'placeholder' => $this->getContentPlaceholder()
                ],
                'constraints' => [
                    new Assert\NotBlank()
                ]
            ])
            ->add('active', CheckboxType::class, [
                'label' => 'widget.active',
                'help' => 'widget.active_help',
                'required' => false,
                'attr' => [
                    'class' => 'h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded'
                ]
            ])
            ->add('cacheable', CheckboxType::class, [
                'label' => 'widget.cacheable',
                'help' => 'widget.cacheable_help',
                'required' => false,
                'attr' => [
                    'class' => 'h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded'
                ]
            ])
            ->add('cacheTime', IntegerType::class, [
                'label' => 'widget.cache_time',
                'help' => 'widget.cache_time_help',
                'attr' => [
                    'class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm',
                    'min' => 60,
                    'max' => 86400,
                    'placeholder' => '3600'
                ],
                'constraints' => [
                    new Assert\Range(['min' => 60, 'max' => 86400])
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Widget::class,
        ]);
    }

    private function getContentPlaceholder(): string
    {
        return <<<HTML
<!-- Exemple pour un widget météo SHOM -->
<div class="textwidget custom-html-widget">
    <script src="https://services.data.shom.fr/hdm/vignette/petite/PORT-NAVALO?locale=fr"></script>
    <iframe width="162" id="vignette_shom_{{unique_id}}" height="350" frameborder="0" scrolling="no"></iframe>
</div>

<!-- Ou du HTML simple -->
<div class="mon-widget">
    <h3>{{title}}</h3>
    <p>Contenu du widget...</p>
</div>
HTML;
    }
}