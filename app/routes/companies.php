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

use Cloud\Model\Company;
use Symfony\Component\HttpFoundation\Request;

/**
 * Get a user
 */
$app->get('/dev', function() use ($app)
{
    return $app->json("dev");
})
;

