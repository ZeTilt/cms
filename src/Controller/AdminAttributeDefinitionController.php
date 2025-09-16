<?php

namespace App\Controller;

use App\Entity\AttributeDefinition;
use App\Repository\AttributeDefinitionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/attribute-definitions', name: 'admin_attribute_definitions_')]
class AdminAttributeDefinitionController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AttributeDefinitionRepository $repository
    ) {}

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $definitions = $this->repository->findBy([], ['entityType' => 'ASC', 'label' => 'ASC']);

        return $this->render('admin/attribute_definitions/index.html.twig', [
            'definitions' => $definitions,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            $label = $request->request->get('label');
            $entityType = $request->request->get('entity_type');
            $fieldType = $request->request->get('field_type', 'text');
            $required = $request->request->has('required');

            $definition = new AttributeDefinition();
            $definition->setName($name)
                ->setLabel($label)
                ->setEntityType($entityType)
                ->setFieldType($fieldType)
                ->setRequired($required);

            if ($fieldType === 'select' || $fieldType === 'radio') {
                $optionsText = $request->request->get('options', '');
                $options = [];
                if ($optionsText) {
                    $lines = explode("\n", $optionsText);
                    foreach ($lines as $line) {
                        $line = trim($line);
                        if ($line && str_contains($line, '=')) {
                            [$key, $value] = explode('=', $line, 2);
                            $options[trim($key)] = trim($value);
                        }
                    }
                }
                $definition->setOptions($options);
            }

            $this->entityManager->persist($definition);
            $this->entityManager->flush();

            $this->addFlash('success', 'Attribut créé avec succès');
            return $this->redirectToRoute('admin_attribute_definitions_index');
        }

        return $this->render('admin/attribute_definitions/new.html.twig', [
            'availableEntities' => $this->getAvailableEntities(),
            'fieldTypes' => $this->getFieldTypes(),
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request): Response
    {
        $definition = $this->repository->find($id);
        if (!$definition) {
            throw $this->createNotFoundException('Définition d\'attribut non trouvée');
        }

        if ($request->isMethod('POST')) {
            $definition->setLabel($request->request->get('label'))
                ->setFieldType($request->request->get('field_type', 'text'))
                ->setRequired($request->request->has('required'))
                ->setActive($request->request->has('active'))
                ->setUpdatedAt(new \DateTimeImmutable());

            $fieldType = $definition->getFieldType();
            if ($fieldType === 'select' || $fieldType === 'radio') {
                $optionsText = $request->request->get('options', '');
                $options = [];
                if ($optionsText) {
                    $lines = explode("\n", $optionsText);
                    foreach ($lines as $line) {
                        $line = trim($line);
                        if ($line && str_contains($line, '=')) {
                            [$key, $value] = explode('=', $line, 2);
                            $options[trim($key)] = trim($value);
                        }
                    }
                }
                $definition->setOptions($options);
            } else {
                $definition->setOptions(null);
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Attribut modifié avec succès');
            return $this->redirectToRoute('admin_attribute_definitions_index');
        }

        $optionsText = '';
        if ($definition->getOptions()) {
            foreach ($definition->getOptions() as $key => $value) {
                $optionsText .= "$key = $value\n";
            }
        }

        return $this->render('admin/attribute_definitions/edit.html.twig', [
            'definition' => $definition,
            'optionsText' => $optionsText,
            'fieldTypes' => $this->getFieldTypes(),
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(int $id, Request $request): Response
    {
        $definition = $this->repository->find($id);
        if (!$definition) {
            throw $this->createNotFoundException('Définition d\'attribut non trouvée');
        }

        if ($this->isCsrfTokenValid('delete_definition_' . $id, $request->request->get('_token'))) {
            $this->entityManager->remove($definition);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Définition d\'attribut supprimée avec succès');
        }

        return $this->redirectToRoute('admin_attribute_definitions_index');
    }

    private function getAvailableEntities(): array
    {
        return [
            'App\\Entity\\User' => 'Utilisateur',
            'App\\Entity\\Event' => 'Événement',
        ];
    }

    private function getFieldTypes(): array
    {
        return [
            'text' => 'Texte libre',
            'email' => 'Email',
            'tel' => 'Téléphone',
            'date' => 'Date',
            'number' => 'Nombre',
            'select' => 'Liste déroulante',
            'radio' => 'Boutons radio',
            'checkbox' => 'Case à cocher',
            'textarea' => 'Texte long',
        ];
    }
}