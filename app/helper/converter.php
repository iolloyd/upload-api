<?php
/**
 * Parameter Converters
 */

use Cloud\Silex\Converter\DoctrineOrmConverter;

$app['converter'] = $app->protect(function($entityName) use ($app) {
    return new DoctrineOrmConverter($app['em'], $entityName);
});

$app['converter.video'] = $app['converter']('Cloud\Model\Video');
$app['converter.videooutbound'] = $app['converter']('Cloud\Model\VideoOutbound');

