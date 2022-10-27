.. _contacts:

Contacts and Users
==================

Contacts in Twyne represent :ref:`post <posts>` Authors, and can also be made into site Users.
When a new Twyne site is installed an initial Contact and matching User are created,
with full admin rights for everything.

All Contacts have a name, homepage URL, and two descriptions (one public, one private).
Contacts that are also site users have, in addition, a username and email address,
and a set of user groups that they belong to.

Creating a Contact
------------------

There are various ways to create a new Contact:

* Go to 'Contacts' in the main menu, and then the 'New contact' link.
* When editing a post, or in the upload form, the author field is free-text.
  If an unknown author name is used, a new Contact will be created.
* Registering a new user account will result in a matching Contact being created.

User Authentication
-------------------

All users are identified by their username, and authenticate with a password.
Two-factor authentication is also enabled by default,
requiring the use of an TOTP app.

If a user loses their 2FA credentials, their account can be reset by using the reset command, e.g.::

    ./bin/console twyne:reset-2fa --username alice

This will force then to re-register a 2FA device.
