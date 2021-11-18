.. _tags:

Tags
====

Tags are the primary way of grouping Posts together by topic; each Post can have any number of Tags.
Each Tag has its own page (with a URL of the form ``/Txx``, where ``xx`` is the Tag's ID)
and on that page it has
a title (which must be unique, and which can be changed whenever required),
and optional description (which uses the same syntax as Post bodies).

A Tag's page has a list of Posts, which is paginated if there are more than ten posts.
The first page has a URL of the form ``/Txx`` and subsequent pages ``/Txx/page-n`` (where ``n`` is 2 or greater).
The layout of each post is the same as for the chronological listings of posts.

Wikidata
--------

Tags can also be linked to Wikidata_ items.
This is the way to show that a Tag's topic is *the same as* the concept represented by the Wikidata item.
For example, a Tag with a title of "Douglas Adams" would have the Wikidata item ID of "Q42_".
Only one Tag can be linked to each Wikidata item.
Adding a Wikidata link to a Tag adds a metadata table to the Tag's page,
showing all the relevant metadata from the Wikidata item.
If a row in the metadata table refers to another Tag that is also linked to Wikidata,
then the value displayed will be a link to that Tag.

Wikidata items can be merged_ (when multiple are accidentally created for the same concept),
but this doesn't affect the linkage from Twyne.
The only change will be that the new ID for the item will be displayed in the metadata table;
no data in the database is changed, because the old ID will remain on Wikidata as a permanent alias for the new one.

The metadata from Wikidata is fetched up every time someone views a Tag's page, and is cached for two weeks.
The cache can be cleared (forcing it to fetch the latest data) by running the following CLI command::

    ./bin/console cache:pool:clear cache.app

.. _Wikidata: https://www.wikidata.org/
.. _Q42: https://www.wikidata.org/wiki/Q42
.. _merged: https://www.wikidata.org/wiki/Help:Merge

Merging
-------

Sometimes, multiple tags might be created that cover the same topic or concept.
In this case, they can be merged.
Merging tags moves all of a tag's posts to another tag,
and then deletes the first tag (leaving behind a redirect, to ensure existing URLs do not break).

To merge a tag, first go to the tag that you want to merge and follow the 'Merge' link.
Then, enter the ID of the tag into which you want to merge.
The next step presents you with both tags and their details,
as well as a form with which to modify the details of the destination tag
(for example, to add to the existing description, or update the Wikidata ID).

Be warned that there is *no way* to undo a tag merge!
