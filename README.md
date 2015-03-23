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

<<<<<<< HEAD

PHP Resque workers
------------------

Workers wait for jobs to be put into the queue. As soon as a job appears, the workers 'pop' the next job and do their work.

Each job has the possibility of four states:

<<<<<<< HEAD
1 => Waiting
2 => Running
3 => Failed
4 => Complete
Silex Extensions
=======
- 1 => Waiting
- 2 => Running
- 3 => Failed
- 4 => Complete

Silex Extensions
---------------
>>>>>>> feature/transcode

We use an extended class Cloud\Silex\Application with support for injecting callable functions into $app based on Pimple.

Filesystem Layout

The application follows the following layout, roughly based on http://www.slimframework.com/news/how-to-organize-a-large-slim-framework-application

No images, css or js is stored with this API code. All frontend code is handled in the cloudxxx-ng Angular.js application.

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
app/config/

Configuration is included automatically from here depending on the environment. Slim uses an environment variable named SLIM_MODE to set the application mode to that variable's value. Each file has the $app variable with all its injected dependencies available.

<<<<<<< HEAD
=======
Workers wait for jobs to be put into the queue. As soon as a job
appears, the workers 'pop' the next job and do their work.

Each job has the possibility of four states:
=======
// app/config/development.ini

[db.options]
driver   = pdo_mysql
host     = localhost
dbname   = cloudxxx
user     = root
password = root
charset  = utf8

[s3]
- secret = foo
- key = bar
>>>>>>> feature/transcode

    1 => Waiting
    2 => Running
    3 => Failed
    4 => Complete

Silex Extensions
---------------

<<<<<<< HEAD
We use an extended class `Cloud\Silex\Application` with support for injecting
callable functions into `$app` based on [Pimple](http://pimple.sensiolabs.org/).

Filesystem Layout
-----------------

The application follows the following layout, roughly based on
http://www.slimframework.com/news/how-to-organize-a-large-slim-framework-application

No images, css or js is stored with this API code. All frontend code is
handled in the `cloudxxx-ng` Angular.js application.
=======
```php
<?php
// app/routes/foobar.php

$app->get('/foobar', function () use ($app) {
    echo 'Hello World';
});

```
>>>>>>> feature/transcode

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

<<<<<<< HEAD
### app/config/

Configuration is included automatically from here depending on the
environment. Slim uses an environment variable named `SLIM_MODE` to set
the application mode to that variable's value. Each file has the `$app`
variable with all its injected dependencies available.
=======
Private library code of the `Cloud\...` namespace is kept here and
can be autoloaded. Use this directory to store all reusable classes.
>>>>>>> feature/transcode

