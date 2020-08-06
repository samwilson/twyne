<?php

namespace Samwilson\Twyne\Tests;

use Samwilson\Twyne\Data\Contact;
use Samwilson\Twyne\Data\User;

class ContactTest extends TestCase
{

    public function testContactsBasics()
    {
        // Register a new user (which will also create a matching contact).
        $user = User::register('Contact Owner', 'user@example.org', 'pwd123');
        // Add a second contact.
        $contact = new Contact();
        $contact->setName('Bob');
        $contact->setUser($user);
        $contact->setDescription('A description.');
        $contact->save();
        // Check that they both exist (they're returned in the order they're created).
        $all = $contact->getAll();
        static::assertCount(2, $all);
        static::assertEquals($all[0]->c_name, 'Contact Owner');
        static::assertEquals($all[1]->c_name, 'Bob');
        static::assertEquals($all[1]->c_description, 'A description.');
    }
}
