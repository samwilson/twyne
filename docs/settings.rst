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

Custom CSS and Scripts
----------------------

It is possible to add custom CSS and Javascript
that is loaded on every page.
This can be useful for things such as modifying the appearance of your site or
adding statistics-logging Javascript.
It is not a fully-fledged theming or plugin system,
but can certainly be used for many common tweaks.

To add or edit the custom CSS or Javascript,
navigate to the *Settings* page, and go to the *Styles* or *Scripts* tab.
Each of these is a single text box,
the contents of which will be loaded verbatim on almost every page on the site.
The only pages on which it is not loaded are the two pages on which the code is edited,
in order to avoid errors in the custom code making it harder to modify that same code.

There is no linting or error-checking of any sort done to the text that you enter, so be careful —
this feature gives you the power to break the site!
If for whatever reason you are not able to using the main navigation links,
the two pages can be found at ``https://example.com/settings/css`` and ``https://example.com/settings/js``.

Both code-editing text boxes have syntax-highlighting enabled.
