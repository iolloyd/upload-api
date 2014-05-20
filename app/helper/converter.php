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

$app['converter.company'] = $app['converter'](
    'Cloud\Model\Company'
);

$app['converter.tag'] = $app['converter'](
    'Cloud\Model\Tag'
);

$app['converter.user'] = $app['converter'](
    'Cloud\Model\User'
);

$app['converter.video'] = $app['converter'](
    'Cloud\Model\Video'
);

$app['converter.inbound'] = $app['converter'](
    'Cloud\Model\VideoInbound'
);

$app['converter.outbound'] = $app['converter'](
    'Cloud\Model\VideoOutbound'
);

$app['converter.tags.from.request'] = $app->protect(function($tags) use ($app) {
  $tags = json_decode($tags);
  $tags = array_map(function($x) use ($app) {
      return $app['converter.tag']->convert($x->id);
    },
    $tags
  );

  return $tags;
});

