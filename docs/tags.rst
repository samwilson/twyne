.. _tags:

Tags
====

Tags are the primary way of grouping Posts together by topic; each Post can have any number of Tags.
Each Tag has its own page (with a URL of the form ``/Txx``, where ``xx`` is the Tag's ID)
and on that page it has
a title (which must be unique),
and optional description (which uses the same syntax as Post bodies).

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
