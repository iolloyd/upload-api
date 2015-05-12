Video files repository
----------------------

*Setup for development*

The application uses composer to manage both file dependancies and autoloading.

Each time you pull code from the repository, it is good practice to run composer update to make sure that you have all required dependancies for the current code.

Composer workflow

Install composer in your project:

curl -s https://getcomposer.org/installer | php --
Install via composer:

php composer.phar install
Update after changing composer.json:

php composer.phar update

Note: Never run composer update in production.

*Run the Development Server*

php -S 0.0.0.0:8080 -t public/ public/index.php

Silex Extensions
---------------


    app/
        config/
            development.php
            production.php.dist
        helper/
            converter.php 
            ...
        routes/
            session.php
            member.php
            admin.php
    bin/
        cli-script.php
    public/
        .htaccess
        index.php
    src/
        Cloud/
            PrivateFramework/
                SomeClass.php
            AnotherPrivateComponent/
                AnotherClass.php
    vendor/
    composer.json
    autoload.php

Private library code of the `Cloud\...` namespace is kept here and
can be autoloaded. Use this directory to store all reusable classes.
