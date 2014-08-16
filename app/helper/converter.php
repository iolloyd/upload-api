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

use Cloud\Silex\Converter\DeserializeConverter;
use Cloud\Silex\Converter\DoctrineOrmConverter;

/* var $app \Silex\Application */

$app['converter'] = $app->protect(function($entityName) use ($app) {
    return new DoctrineOrmConverter($app['em'], $entityName);
});

$app['deserialize_converter'] = $app->protect(function($className) use ($app) {
    return new DeserializeConverter($app['serializer'], $className);
});

// --

$app['converter.category'] = $app['converter'](
    'Cloud\Model\Category'
);

$app['converter.company'] = $app['converter'](
    'Cloud\Model\Company'
);

$app['converter.inbound'] = $app['converter'](
    'Cloud\Model\VideoInbound'
);

$app['converter.outbound'] = $app['converter'](
    'Cloud\Model\VideoOutbound'
);

$app['converter.site'] = $app['converter'](
    'Cloud\Model\Site'
);

$app['converter.tag'] = $app['converter'](
    'Cloud\Model\Tag'
);

$app['converter.tubesite'] = $app['converter'](
    'Cloud\Model\Tubesite'
);

$app['converter.user'] = $app['converter'](
    'Cloud\Model\User'
);

$app['converter.video'] = $app['converter'](
    'Cloud\Model\Video'
);
