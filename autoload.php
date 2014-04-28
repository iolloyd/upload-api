<?php

define('REQUEST_MICROTIME', microtime(true));

if (!file_exists('vendor/autoload.php')) {
    throw new RuntimeException('Unable to load vendor libraries. Run `php composer.phar install`');
}

$autoloader = require 'vendor/autoload.php';

if (!class_exists('Slim\Slim')) {
    throw new RuntimeException(
        'Unable to load vendor libraries. Run `php composer.phar install`'
    );
}

$autoloader->add('Cloud\\', 'src/');
