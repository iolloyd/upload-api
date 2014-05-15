<?php
/**
 * cloudxxx-api (http://www.cloud.xxx)
 *
 * Copyright (C) 2014 Really Useful Limited.
 * Proprietary code. Usage restrictions apply.
 *
 * @copyright  Copyright (C) 2014 Really Useful Limited
 * @license    Proprietary
 */

/**
 * Parameter Converters
 */

use Cloud\Silex\Converter\DoctrineOrmConverter;

$app['converter'] = $app->protect(function($entityName) use ($app) {
    return new DoctrineOrmConverter($app['em'], $entityName);
});

$app['converter.video'] = $app['converter'](
    'Cloud\Model\Video'
);

$app['converter.inbound'] = $app['converter'](
    'Cloud\Model\VideoInbound'
);

$app['converter.outbound'] = $app['converter'](
    'Cloud\Model\VideoOutbound'
);


