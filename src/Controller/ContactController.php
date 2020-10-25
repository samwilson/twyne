<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Repository\ContactRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

class ContactController extends AbstractController
{

    /**
     * @Route("/contacts", name="contacts")
     */
    public function allContacts(ContactRepository $contactRepository)
    {
        return $this->render('contact/index.html.twig', [
            'contacts' => $contactRepository->findAll(),
        ]);
    }

    /**
     * @Route("/contact/new", name="contact_create")
     * @Route("/C{id}/edit", name="contact_edit", requirements={"id"="\d+"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function editContact(ContactRepository $contactRepository, $id = null)
    {
        $contact = $id ? $contactRepository->find($id) : new Contact();
        return $this->render('contact/form.html.twig', [
            'contact' => $contact,
        ]);
    }

    /**
     * @Route("/contact/save", name="contact_save")
     * @IsGranted("ROLE_ADMIN")
     */
    public function saveContact(
        Request $request,
        ContactRepository $contactRepository,
        EntityManagerInterface $entityManager
    ) {
        $id = $request->get('id');
        $contact = $id ? $contactRepository->find($id) : new Contact();
        $contact->setName($request->get('name'));
        $contact->setHomepage($request->get('homepage'));
        $contact->setDescriptionPublic($request->get('description_public'));
        $contact->setDescriptionPrivate($request->get('description_private'));
        $entityManager->persist($contact);
        $entityManager->flush();
        return $this->redirectToRoute('contact_view', ['id' => $contact->getId()]);
    }

    /**
     * @Route("/C{id}", name="contact_view", requirements={"id"="\d+"})
     */
    public function viewContact(ContactRepository $contactRepository, $id = null)
    {
        $contact = $contactRepository->find($id);
        if ($contact->getPosts()->count() === 0 && !$this->getUser()) {
            throw $this->createAccessDeniedException();
        }
        return $this->render('contact/view.html.twig', [
            'contact' => $contact,
        ]);
    }
}
