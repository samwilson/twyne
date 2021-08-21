.. _config:

Configuration
=============

All the important parts of configuration of a Twyne site
happen in the ``.env.local`` file::

    APP_SECRET=a-long-random-string
    APP_MAIL_SENDER=admin@example.org
    APP_LOG_RECIPIENT=admin@example.org
    MAILER_DSN=smtp://username:password@smtp.gmail.com

Other configuration happens on the Settings page,
accessible via the main menu.
