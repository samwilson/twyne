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

Maps
----

Maps are shown for individual Posts (both when viewing and editing),
and for all posts on the ``/map`` page.
The tiles used for these maps can be configured via the following four environment variables:

.. code-block:: shell

   APP_MAP_TILES_VIEW_URL="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
   APP_MAP_TILES_VIEW_CONFIG="{\"label\": \"OSM\", \"attribution\": \"&copy; <a href='https://openstreetmap.org/copyright'>OpenStreetMap contributors</a>\", \"maxZoom\": \"19\"}"
   APP_MAP_TILES_EDIT_URL="https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}"
   APP_MAP_TILES_EDIT_CONFIG="{\"label\": \"Esri\", \"attribution\": \"&copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community\", \"maxZoom\": \"19\"}"

The values shown here are the defaults as given in ``.env``,
and they use the OpenStreetMap rendered map, and Esri satellite imagery.

The ``*_CONFIG`` variables are JSON, and must be well-formed and correctly escaped.
To check that you have set their values correctly, browse to ``/map-config.json``
and confirm that you see the desired structured output.

Logged-in users can switch between the two sets of tiles via a layer selector in the top right of the map.
The ``label`` value for each ``*_CONFIG`` variable is what sets the user-visible label that's shown in the selector.

The 'EDIT' tiles (by default, the satellite imagery) are only shown to logged-in users,
because it can be useful to use a restricted or expensive source for these tiles.

The `Leaflet Providers`_ tool can be useful for finding compatible tiles and their required configuration
(as well as things such as whether they require registration).

.. _`Leaflet Providers`: https://leaflet-extras.github.io/leaflet-providers/preview/index.html
