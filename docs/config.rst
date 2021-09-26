.. _config:

Configuration
=============

All the important parts of configuration of a Twyne site
happen in the ``.env.local`` file.
The minimum that should be set are as follows:

.. code-block:: shell

   APP_SECRET=a-long-random-string
   APP_MAIL_SENDER=admin@example.org
   APP_LOG_RECIPIENT=admin@example.org
   MAILER_DSN=smtp://username:password@smtp.gmail.com

Other configuration
(that is likely to be changed occasionally during the normal operation of the site)
happens on the :ref:`settings` page,
accessible via the main menu.

Uploaded files
--------------

There are two options for file storage: the local filesystem, and S3_-compatible object stores
(such as those from AWS, Digital Ocean, OVH, Dreamhost, etc.).

.. _S3: https://en.wikipedia.org/wiki/Amazon_S3

The local filesystem is the default, and files will be stored in the ``var/app_data/`` directory.
This location can be changed via ``.env.local``:

.. code-block:: shell

   APP_FS_DATA_DIR=/path/to/your/data_dir/

To use S3-compatible storage, set the following environment variables:

.. code-block:: shell

   APP_FS_DATA_STORE=aws
   APP_FS_AWS_REGION=
   APP_FS_AWS_ENDPOINT=
   APP_FS_AWS_BUCKET=
   APP_FS_AWS_KEY=
   APP_FS_AWS_SECRET=

Uploaded files will have various derivative files created for them, such as thumbnails of images.
These files will be stored in the ``var/app_temp/`` directory by default.
This directory can be changed in ``.env.local``:

.. code-block:: shell

   APP_FS_TEMP_DIR=/path/to/your/tmp_dir/

The temporary directory contains only files that will be regenerated as required
(hence the name 'temporary'; these are however long-lived files).
There is no need to back up this directory.
