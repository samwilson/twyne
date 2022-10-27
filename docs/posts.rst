.. _posts:

Posts
=====

Posts are the core data structure of Twyne,
forming a chronological series of writing and uploaded files.
At their most basic, they have
an author,
a date and time,
and a title or body.
These and Posts' other attributes are explained in full below.

In addition to these attributes,
Posts can also have :ref:`tags` and :ref:`syndications`.

ID
--

Each Post has an integer identifier,
which forms part of its URL.
For example, Post 123 has a URL of ``https://example.org/P123``.

The ``P`` does not form part of the ID,
but is added in many contexts to help distinguish Post IDs from other IDs such as those of Tags.
Anywhere that a Post ID can be entered (for example, in the reply-to field),
both the prefixed and un-prefixed forms are acceptable,
and will be normalized to the prefixed form.

Date
----

Dates and times are stored in the UTC timezone,
but can be entered in whatever timezone you prefer.
They will be displayed in the user's timezone
(when the site is viewed in a web browser; for other contexts such as RSS feeds, UTC is used).

Author
------

A Post must have an author (even if that author is the ever-prolific "anon.").
A site's default author can be set with the ``APP_MAIN_CONTACT`` environment variable,
which is a reference to the ID of whichever :ref:`Contact <contacts>` should be the default
(usually this is the first user, which is created at install-time).

Title
-----

Titles are short unformatted strings.
A Post does not have to have a title,
in which case it will be displayed slightly differently in post lists
and on its own page.
Titles do not have to be unique.

Body
----

The body of a Post is the main, possibly multi-paragraph, text
that is formatted according to Twyne's Markdown syntax.

Location
--------

GPS coordinates can be saved for any Post.
A link to the Wikimedia Geohack tool will be provided,
as a map-pin emoji, for example: `📍`_.

.. _`📍`: https://geohack.toolforge.org/geohack.php?params=32.05694_S_115.74131_E

User group
----------

A Post is only viewable a single User Group.
By default this is the "Public" group.

Note that Users can be in multiple groups.

File
----

A Post can have an attached file,
which can be an image (JPEG, PNG, or GIF format), or PDF.

When uploading a file such as a JPEG that has embedded GPS coordinates,
the Post's Locaiton field will also be set (if it doesn't already have a location).

Original URL
------------

If a Post is being syndicated from another site,
it will have a link back to its canonical location on that site.

Replies
-------

A Post can be in reply_ to another post.
Often, that other post will be a syndication from another site.
Replies in Twyne are similar to what are called 'comments' in other blogging systems (such as WordPress).

On any Post page there is a 'reply' link,
which goes to a new-post form with the "In reply to" field already filled in.

.. _reply: https://indieweb.org/reply
