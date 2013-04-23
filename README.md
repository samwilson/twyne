Twyne
=====

A photograph database system.

Installing
----------

1. Clone the git repository: `git clone https://github.com/samwilson/twyne.git`
2. Initialise and update the submodules: `git submodule init; git submodule update`
3. Copy `config_sample.php` to `config.php` and edit it to include database connection informtion.
4. Copy `htaccess_sample` to `.htaccess` and change the `RewriteBase` if required.
5. Run `resources/sql/*.sql` files in chronological order.  **Don't run any of them more than once.**

Note: the first user will be created when you log in for the first time, with administrative privileges.

Licence
-------

Twyne is Free Software, released under the GNU General Public License (GPL) version 3.
