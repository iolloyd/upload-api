<?php

//define('REQUEST_MICROTIME', microtime(true));

if (!file_exists('vendor/autoload.php')) {
    throw new RuntimeException('Unable to load vendor libraries. Run `php composer.phar install`');
}

$autoloader = require __DIR__ . '/vendor/autoload.php';
if (!class_exists('Silex\Application')) {
    throw new RuntimeException(
        'Unable to load vendor libraries. Run `php composer.phar install`'
    );
}

$autoloader->add('Cloud\\', 'src/');
$autoloader->add('CloudOutbound\\', 'src/');
$autoloader->add('CloudTest\\', 'tests/');
