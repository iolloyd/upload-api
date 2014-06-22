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

/* var $app \Silex\Application */
$app['logger.api']      = $app['monolog.factory']('api');
$app['logger.doctrine'] = $app['monolog.factory']('doctrine');
$app['logger.security'] = $app['monolog.factory']('security');

