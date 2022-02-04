<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Entity\User;
use App\Entity\UserGroup;
use App\Repository\ContactRepository;
use App\Repository\UserGroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class ContactController extends ControllerBase
{

    /**
     * @Route("/contacts", name="contacts")
     * @IsGranted("ROLE_ADMIN")
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
    public function editContact(
        ContactRepository $contactRepository,
        UserGroupRepository $userGroupRepository,
        $id = null
    ) {
        $contact = $id ? $contactRepository->find($id) : new Contact();
        return $this->render('contact/form.html.twig', [
            'contact' => $contact,
            'user_groups' => $userGroupRepository->findAll(),
        ]);
    }

    /**
     * @Route("/contact/save", name="contact_save")
     * @IsGranted("ROLE_ADMIN")
     */
    public function saveContact(
        Request $request,
        ContactRepository $contactRepository,
        UserGroupRepository $userGroupRepository,
        EntityManagerInterface $entityManager,
        UserPasswordEncoderInterface $passwordEncoder
    ) {
        $id = $request->get('id');
        $contact = $id ? $contactRepository->find($id) : new Contact();
        $contact->setName($request->get('name'));
        $contact->setHomepage($request->get('homepage'));
        $contact->setDescriptionPublic($request->get('description_public'));
        $contact->setDescriptionPrivate($request->get('description_private'));

        $username = $request->get('username');
        if ($username) {
            $user = $contact->getUser() ?? new User();
            $user->setUsername($username);
            $user->setEmail($request->get('email'));
            $user->setContact($contact);
            if (!$user->getPassword()) {
                // If this is a new user, create a random password.
                $user->setPassword($passwordEncoder->encodePassword($user, uniqid()));
            }
            $user->setGroups(new ArrayCollection());
            foreach ($request->get('user_groups', []) as $groupId => $value) {
                $user->addGroup($userGroupRepository->find($groupId));
            }
            $newGroup = $request->get('new_group');
            if ($newGroup) {
                $group = new UserGroup();
                $group->setName($newGroup);
                $user->addGroup($group);
                $entityManager->persist($group);
            }
            $entityManager->persist($user);
        }

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
