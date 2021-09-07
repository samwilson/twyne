.. _syndications:

Syndications
============

Syndications are Twyne's implementation of the POSSE_ idea from the indieweb:
to *Post on one's Own Site, and Syndicate Elsewhere*.
This means that a Post is first created on the Twyne site,
and once it's saved it's then posted to other sites
and linked back to the canonical version on the Twyne site.
The Syndication data is saved against the Post,
as a URL and label pair.
The label can be anything,
and is often the name of the site on which the Syndication has been saved.

.. _POSSE: https://indieweb.org/POSSE

For example,
a short one-sentence post with no title might be made in Twyne
and then tweeted in Twitter.
The tweet would have the content of the post and its URL.
After the tweet has been made, and has been given its own URL,
that URL (and the label 'Twitter')
will be added as a Syndication on the original post in Twyne.

Because syndicating to other sites is so common,
Twyne tries to make this as easy as possible.
For instance, when first saving a Post it may be simplest to click 'Save and keep editing',
in order to remain in the editing form while you switch over to the other site in a separate tab,
and then come back to save the new Syndication URL and label.
Or, for Wikimedia Commons, there is a special exporting system
that makes it much more streamlined to upload a file to that repository
(along with all its metadata, and a link back to the Twyne site).
This is explained below.

Other quick methods of adding Syndications are planned.
If you have any ideas, please open an issue on Github.

Copy to Wikimedia Commons
-------------------------

For syndicating files to `Wikimedia Commons`_,
you first need to become familiar with what Commons is, and what sorts of files are wanted on the project.
It's best to make your `first contributions`_ via the Upload Wizard, rather than directly from Twyne,
so you get an iea of how things generally work.
Then, if you do want to set up the quick-upload-from-Twyne,
you need to `create a Bot Password`_.
Enter your site's name in the "Bot name" field (in the "Create a new bot password" section)
and grant it the following permissions:

* Edit existing pages
* Create, edit, and move pages
* Upload new files
* Upload, replace, and move files

After saving the new Bot Password, it will give you a username and password.
These need to be added to your ``.env.local`` file:

.. code-block:: shell

   APP_COMMONS_USERNAME=Your_Username@Bot_Name
   APP_COMMONS_PASSWORD=TheBotPassword123ABC

.. _`first contributions`: https://commons.wikimedia.org/wiki/Commons:First_steps/Uploading_files
.. _`Wikimedia Commons`: https://commons.wikimedia.org/
.. _`create a Bot Password`: https://commons.wikimedia.org/wiki/Special:BotPasswords

Now, when editing a Post, a link to 'Copy to Wikimedia Commons' will appear below the syndications table.
Clicking this will take you to a form that shows a preview of the file,
and fields for all the required meatadata.
The full wikitext of the page can be modified at this point,
along with the Structured Data caption_ and the depicts_ statements.
For more information about how to use each of these elements,
please read the Commons documentation.

.. _caption: https://commons.wikimedia.org/wiki/Commons:File_captions
.. _depicts: https://commons.wikimedia.org/wiki/Commons:Depicts
