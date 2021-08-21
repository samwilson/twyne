Installing and upgrading
========================

Prerequesites
-------------

To install Twyne, you need the following:

1. a web server with PHP 7.3 or above;
2. command-line access to that server;
3. the `Git`_ version control system; and
4. the PHP package manager, `Composer`_.

.. _`Git`: https://git-scm.com/
.. _`Composer`: https://getcomposer.org/

Downloading
-----------

First, clone the latest version of the source code
into a non web-accessible location on your server::

    git clone https://github.com/samwilson/twyne.git /var/www/twyne

Create a new database and grant access to it to a new user.
Add the details of this database and the user's credentials
to the ``.env.local`` file in the ``DATABASE_URL`` key::

    DATABASE_URL=mysql://user_name:password@127.0.0.1:3306/db_name

Then install the application with the included script,
which checks out the latest version and installs all dependencies with Composer::

    /var/www/twyne/bin/deploy.sh prod /var/www/twyne

Next, set up your web server to serve the ``public/`` directory
as the root of the new web site.
For Apache, this could be something like the following::

    <VirtualHost *:80>
        ServerName example.org
        RewriteRule ^/(.*)$ https://example.org/$1 [L,R=permanent,QSA]
    </VirtualHost>
    <VirtualHost *:80>
        ServerName www.example.org
        RewriteRule ^/(.*)$ https://example.org/$1 [L,R=permanent,QSA]
    </VirtualHost>
    <VirtualHost *:443>
        ServerName example.org
        DocumentRoot /var/www/twyne/public/
        SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1
        <Directory "/var/www/twyne/public/">
            RewriteRule ^index\.php$ - [L]
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteCond %{REQUEST_FILENAME} !-d
            RewriteRule . /index.php [L]
        </Directory>
    </VirtualHost>

Where ``example.org`` is replaced by your own domain name.

Your new Twyne site should now be live.
Navigate to the home page,
and continue the set-up process as detailed in the :ref:`config` section of this manual.

Upgrading
---------

Upgrading is as simple as re-running the ``deploy.sh`` script.
This will checkout the latest version of the code,
install dependencies,
and update the database as required.
This script can be run automatically on a daily or weekly basis
(as a Cronjob or Scheduled Task, for instance).
