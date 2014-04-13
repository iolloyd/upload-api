cloud.xxx
=========

Video files repository

Setup for development
---------------------

The application uses [composer](https://getcomposer.org) to manage both
file dependancies and autoloading.

Each time you pull code from the repository, it is good practice to run
`composer update` to make sure that you have all required dependancies
for the current code.

Composer workflow
-----------------

Install composer in your project:

    curl -s https://getcomposer.org/installer | php --

Install via composer:

    php composer.phar install

Update after changing `composer.json`:

    php composer.phar update

*TODO Review*
When the app is deployed to production, we deploy
the *composer.lock* file and then run `composer install`.

Note: Never run `composer update` in production.

Run the Development Server
--------------------------

Start the internal PHP cli-server in the root directory:

    php -S 0.0.0.0:8080 -t public/ public/index.php

This will start the cli-server on port 8080, and bind it to all network interfaces.

**Note:** The built-in CLI server is for development only.

PHP Resque workers
------------------

Workers wait for jobs to be put into the queue. As soon as a job
appears, the workers 'pop' the next job and do their work.

Each job has the possibility of four states:

    1 => Waiting
    2 => Running
    3 => Failed
    4 => Complete

Slim Extensions
---------------

We use an extended class `Cloud\Slim\Slim` with support for injecting
callable functions into `$app`.

The convention is to follow Node.js Express when deciding which
functionality to include directly in `$app`:

    $app->json(array $body)

Filesystem Layout
-----------------

The application follows the following layout, roughly based on
http://www.slimframework.com/news/how-to-organize-a-large-slim-framework-application

No images, css or js is stored with this API code. All frontend code is
handled in the `cloudxxx-ng` Angular.js application.

    app/
        config/
            development.php
            production.php.dist
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

### app/config/

Configuration is included automatically from here depending on the
environment. Slim uses an environment variable named `SLIM_MODE` to set
the application mode to that variable's value. Each file has the `$app`
variable with all its injected dependencies available.

```php
<?php
// app/config/development.php

$app->configureMode('development', function () use ($app) {
    $app->config([
        'debug' => true,
        'log.enabled' => false,
    ]);
});
```

### app/routes/

Related routes and logic are loaded automatically from here. Each file
has the `$app` variable with all its injected dependencies available.

```php
<?php
// app/routes/foobar.php

$app->get('/foobar', function () use ($app) {
    echo 'Hello World';
});
```

### src/Cloud/

Private library code of the `Cloud\...` namespace is kept here and
can be autoloaded. Use this directory to store all reusable classes.

This folder follows the PSR-4 structure.
