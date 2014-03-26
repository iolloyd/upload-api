cloud.xxx
=========

Video files repository

Setup for development
---------------------

The application uses [composer](https://getcomposer.org) to 
manage both file dependancies and autoloading.

Each time you pull code from the repository, it is good practice
to run `composer update` to make sure that you have all required
dependancies for the current code.

Composer workflow
-----------------

Add dependancies to composer.json.
`composer install`
Add more dependancies
`composer update`

When the app is deployed to production, we deploy
the *composer.lock* file and then run `composer install`.

Note: Never run `composer update` in production.
