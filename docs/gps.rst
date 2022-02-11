.. _gps:

GPS tracks
==========

Twyne has the ability to incorporate `GPS tracks`_
as collected by various tracking devices and apps.
These tracks can make it easier geolocate Posts.

The tracks will be shown to site admins (only) as blue dots on the maps.
Not *all* points will be shown (because especially when zoomed out, there will likely be many thousands),
but a random selection, to give an estimate of routes.

.. _`GPS tracks`: https://en.wikipedia.org/wiki/GPS_tracking_unit

Overland
--------

Overland_ is a mobile app that's available for Android_ and iOS_,
and while enabled will keep a log of everywhere the phone goes
(even when either the phone or the Twyne server is offline).
Track points will be periodically sent to Twyne.

To configure: after installing the app go to Settings
and enter a new password in the 'Overland key' field.
This same password then needs to be entered in Overland as part of the 'Receiver Endpoint' URL.
For example, if Twyne is installed at https://example.org/ and you set an Overland key of ``23P945B86N23``
then the end point should be::

    http://example.org/overland?key=23P945B86N23

.. _Overland: https://indieweb.org/Overland
.. _Android: https://play.google.com/store/apps/details?id=com.openhumans.app.overland
.. _iOS: https://overland.p3k.app/download
