Twyne
=====

A web-based journal for writing and photography.

![CI](https://github.com/samwilson/twyne/workflows/CI/badge.svg)

## Requirements

* PHP (7.2 or above)
* MariaDB (10.3 or above) or MySQL (5.7 or above)
* Shell access

## Installation

1. Clone: `git clone https://github.com/samwilson/twyne`
2. Install: `cd twyne` then `composer install`
3. Edit the details in `.env.local`:

       APP_ENV=prod
       APP_SECRET=random-string-here
       DATABASE_URL=mysql://dbuser:dbpass@localhost:3306/twyne?serverVersion=5.7

4. Create the database (if needed): `./bin/console doctrine:database:create`
5. Install the database: `./bin/console doctrine:migrations:migrate`
6. Open the site in a browser. The first account you create will be the administrator.
