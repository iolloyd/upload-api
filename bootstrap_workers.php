<?php
require_once "vendor/autoload.php";

use Symfony\Component\ClassLoader\UniversalClassLoader;

$loader = new UniversalClassLoader();
$loader->register();
$loader->registerNamespace('Cloud', __DIR__.'/src');
