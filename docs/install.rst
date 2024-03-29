Installing and upgrading
========================

Prerequesites
-------------

To install and use Twyne,
you need the following software on your web server:

1. a web server with PHP 7.3 or above;
2. MariaDB (10.3 or above) or MySQL (5.7 or above)
3. command-line access to that server;
4. the Git_ version control system;
5. the PHP package manager, `Composer`_;
6. ExifTool_ for working with embedded photo metadata; and
7. ImageMagick_ for creating multiple sizes of images.

.. _Git: https://git-scm.com/
.. _Composer: https://getcomposer.org/
.. _ExifTool: https://exiftool.org/
.. _ImageMagick: https://imagemagick.org/index.php

Downloading
-----------

First, clone the latest version of the source code
into a non web-accessible location on your server:

.. code-block:: shell

   git clone https://github.com/samwilson/twyne.git /var/www/twyne

Create a new database and grant access to it to a new user.
Add the details of this database and the user's credentials
to the ``.env.local`` file in the ``DATABASE_URL`` key:

.. code-block:: shell

   DATABASE_URL=mysql://user_name:password@127.0.0.1:3306/db_name

Then install the application with the included script,
which checks out the latest version and installs all dependencies with Composer:

.. code-block:: shell

   /var/www/twyne/bin/deploy.sh prod /var/www/twyne

Next, set up your web server to serve the ``public/`` directory
as the root of the new web site.
For Apache, this could be something like the following:

.. code-block:: apache

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

Prior to Twyne 0.30.0, location data was not read from any uploaded files that had GPS data in them
(i.e. usually photographs).
To retrospecively add this data, run

.. code-block:: shell

   ./bin/console twyne:extract-gps
