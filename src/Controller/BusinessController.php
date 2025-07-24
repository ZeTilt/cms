<?php

namespace App\Controller;

use App\Entity\BusinessContact;
use App\Service\ModuleManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/business')]
#[IsGranted('ROLE_ADMIN')]
class BusinessController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ModuleManager $moduleManager
    ) {
    }

    #[Route('', name: 'admin_business_dashboard')]
    public function dashboard(): Response
    {
        if (!$this->moduleManager->isModuleActive('business')) {
            throw $this->createNotFoundException('Business module is not active');
        }

        // Statistics
        $totalContacts = $this->entityManager->getRepository(BusinessContact::class)
            ->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $prospects = $this->entityManager->getRepository(BusinessContact::class)
            ->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.status = :status')
            ->setParameter('status', 'prospect')
            ->getQuery()
            ->getSingleScalarResult();

        $leads = $this->entityManager->getRepository(BusinessContact::class)
            ->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.status = :status')
            ->setParameter('status', 'lead')
            ->getQuery()
            ->getSingleScalarResult();

        $clients = $this->entityManager->getRepository(BusinessContact::class)
            ->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.status = :status')
            ->setParameter('status', 'client')
            ->getQuery()
            ->getSingleScalarResult();

        // Recent contacts
        $recentContacts = $this->entityManager->getRepository(BusinessContact::class)
            ->createQueryBuilder('c')
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        // Overdue follow-ups
        $overdueFollowUps = $this->entityManager->getRepository(BusinessContact::class)
            ->createQueryBuilder('c')
            ->where('c.nextFollowUpDate < :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('c.nextFollowUpDate', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        return $this->render('admin/business/dashboard.html.twig', [
            'totalContacts' => $totalContacts,
            'prospects' => $prospects,
            'leads' => $leads,
            'clients' => $clients,
            'recentContacts' => $recentContacts,
            'overdueFollowUps' => $overdueFollowUps,
        ]);
    }

    #[Route('/contacts', name: 'admin_business_contacts')]
    public function contacts(Request $request): Response
    {
        if (!$this->moduleManager->isModuleActive('business')) {
            throw $this->createNotFoundException('Business module is not active');
        }

        $page = max(1, $request->query->getInt('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $queryBuilder = $this->entityManager->getRepository(BusinessContact::class)
            ->createQueryBuilder('c')
            ->orderBy('c.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        $status = $request->query->get('status');
        if ($status) {
            $queryBuilder->andWhere('c.status = :status')
                ->setParameter('status', $status);
        }

        $contacts = $queryBuilder->getQuery()->getResult();

        // Count total for pagination
        $totalContacts = $this->entityManager->getRepository(BusinessContact::class)
            ->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $totalPages = ceil($totalContacts / $limit);

        return $this->render('admin/business/contacts.html.twig', [
            'contacts' => $contacts,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalContacts' => $totalContacts,
            'currentStatus' => $status,
        ]);
    }

    #[Route('/contacts/new', name: 'admin_business_contacts_new')]
    public function newContact(): Response
    {
        if (!$this->moduleManager->isModuleActive('business')) {
            throw $this->createNotFoundException('Business module is not active');
        }

        return $this->render('admin/business/contact_edit.html.twig', [
            'contact' => new BusinessContact(),
            'isEdit' => false,
        ]);
    }

    #[Route('/contacts/{id}/edit', name: 'admin_business_contacts_edit', requirements: ['id' => '\d+'])]
    public function editContact(BusinessContact $contact): Response
    {
        if (!$this->moduleManager->isModuleActive('business')) {
            throw $this->createNotFoundException('Business module is not active');
        }

        return $this->render('admin/business/contact_edit.html.twig', [
            'contact' => $contact,
            'isEdit' => true,
        ]);
    }

    #[Route('/contacts/save', name: 'admin_business_contacts_save', methods: ['POST'])]
    public function saveContact(Request $request): Response
    {
        if (!$this->moduleManager->isModuleActive('business')) {
            throw $this->createNotFoundException('Business module is not active');
        }

        $contactId = $request->request->get('id');
        $contact = $contactId ? $this->entityManager->getRepository(BusinessContact::class)->find($contactId) : new BusinessContact();

        if (!$contact) {
            throw $this->createNotFoundException('Contact not found');
        }

        // Set assigned user if new contact
        if (!$contactId) {
            $contact->setAssignedTo($this->getUser());
        }

        $contact->setFirstName($request->request->get('first_name'));
        $contact->setLastName($request->request->get('last_name'));
        $contact->setEmail($request->request->get('email'));
        $contact->setPhone($request->request->get('phone'));
        $contact->setCompany($request->request->get('company'));
        $contact->setPosition($request->request->get('position'));
        $contact->setStatus($request->request->get('status', 'prospect'));
        $contact->setSource($request->request->get('source', 'website'));
        $contact->setNotes($request->request->get('notes'));

        // Tags
        $tagsString = $request->request->get('tags', '');
        $tags = $tagsString ? array_map('trim', explode(',', $tagsString)) : [];
        $contact->setTags($tags);

        // Follow-up date
        $followUpDate = $request->request->get('next_follow_up_date');
        if ($followUpDate) {
            $contact->setNextFollowUpDate(new \DateTimeImmutable($followUpDate));
        }

        if (!$contactId) {
            $this->entityManager->persist($contact);
        }

        $this->entityManager->flush();

        $this->addFlash('success', 'Contact saved successfully!');
        return $this->redirectToRoute('admin_business_contacts_edit', ['id' => $contact->getId()]);
    }

    #[Route('/contacts/{id}/delete', name: 'admin_business_contacts_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function deleteContact(BusinessContact $contact): Response
    {
        if (!$this->moduleManager->isModuleActive('business')) {
            throw $this->createNotFoundException('Business module is not active');
        }

        $this->entityManager->remove($contact);
        $this->entityManager->flush();

        $this->addFlash('success', 'Contact deleted successfully!');
        return $this->redirectToRoute('admin_business_contacts');
    }
}