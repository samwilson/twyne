<?php

namespace Samwilson\Twyne\Controller;

use Samwilson\Twyne\Data\Contact;
use Samwilson\Twyne\Template;

class ContactController extends ControllerBase
{

    public function indexGet($args)
    {
        $tpl = $this->getTemplate('contacts.html');
        $tpl->stylesheet = 'contacts';
        $tpl->contacts = $this->user ? $this->user->getContacts() : [];
        $this->outputTemplate($tpl);
    }

    public function viewGet($args)
    {
        $id = $args['id'] ?? null;
        $contact = $id ? Contact::loadById($id) : Contact::newForUser($this->user);
        if (!$contact->canBeViewedBy($this->user)) {
            $this->addAlert('warning', 'not-authorized');
        }
        $tpl = $this->getTemplate('contact.html');
        $tpl->stylesheet = 'contacts';
        $tpl->contact = $contact;
        $this->outputTemplate($tpl);
    }

    public function editGet($args)
    {
        $tpl = $this->getTemplate('contact_edit.html');
        $tpl->stylesheet = 'contacts';
        if (!$this->user) {
            $this->addAlert('warning', 'not-authorized');
            $tpl->contact = new Contact();
            $this->outputTemplate($tpl);
            return;
        }
        $id = $args['id'] ?? null;
        $contact = $id ? Contact::loadById($id) : Contact::newForUser($this->user);
        if (!$contact->canBeEditedBy($this->user)) {
            $this->addAlert('warning', 'not-authorized');
        }
        $tpl->contact = $contact;
        $this->outputTemplate($tpl);
    }

    public function savePost()
    {
        if (!$this->user) {
            $this->addAlert('warning', 'not-authorized');
            $this->redirect('/contacts/new');
        }
        $id = $this->getParamPost('id');
        $contact = $id ? Contact::loadById($id) : Contact::newForUser($this->user);
        if (!$contact->canBeEditedBy($this->user)) {
            $this->addAlert('warning', 'not-authorized');
            $this->redirect('/' . ($id ? "C$id/edit" : 'new'));
        }
        $contact->setName($this->getParamPost('name'));
        $contact->setDescription($this->getParamPost('description'));
        $contact->save();
        $this->addAlert(Template::INFO, 'contact-saved');
        $this->redirect('/C' . $contact->getId());
    }
}
