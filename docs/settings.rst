.. _settings:

Settings
========

The site settings area of Tywne is where various aspects of a site can be configured and viewed.
Only administrators can view and change settings.
Settings are different from :ref:`config`, which happens in the ``.env.local`` file.
The difference between the two systems
is that settings are likely to change during normal operation of a site,
but configuration is not.

Site title
----------

The site title is used in a few places, such as the main website header, and in emails that are sent by the site.
It should not contain any HTML.

Allow user registrations?
-------------------------

This setting can be used to turn off new user registrations.
By default, anyone can register a new account on the site
(having an account doesn't grant any special permissions though).

API key
-------

If you use the CLI client, you'll need to set an API key.
This should be any random alphanumeric string, and must be kept secret.

Redirects
---------

In a subpage of Settings is the redirects table.
This is a list of URL redirects and responses.
It's used by the software when posts are deleted or tags merged,
to make sure that former URLs don't stop working.

It can also be used by site owners to add their own redirects, for example
to maintain backwards compatibility of URLs from a different CMS that was previously used for the same domain,
or as a `URL shortening`_ system for simplifying any external or internal URLs.

Redirects have three parts: a path, a destination, and a status.
The path always starts with a slash and is the local URL path (without protocol or domain) that will be redirected.
The destination is either a local path (i.e. starting with a slash)
or an external fully-qualified URL (i.e. to redirect to a different site).
The status is the `HTTP status code`_ that will be used.
A special status is ``410``, which is used when you do not want to redirect a URL,
but rather tell the user that whatever was once at that URL is now *gone*.
No destination is required for those redirects (which aren't really redirects!).

.. _`URL shortening`: https://en.wikipedia.org/wiki/URL_shortening
.. _`HTTP status code`: https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
