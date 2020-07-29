<?php

namespace Samwilson\Twyne\Tests;

use Samwilson\Twyne\Data\Contact;
use Samwilson\Twyne\Data\User;

class ContactTest extends TestCase
{

    public function testContactsBasics()
    {
        $user = User::register('Contact Owner', 'user@example.org', 'pwd123');
        $contact = new Contact();
        $contact->setName('Bob');
        $contact->setUser($user);
        $contact->save();
        $all = $contact->getAll();
        static::assertCount(2, $all);
        static::assertEquals($all[0]->c_name, 'Bob');
        static::assertEquals($all[1]->c_name, 'Contact Owner');
    }
}
