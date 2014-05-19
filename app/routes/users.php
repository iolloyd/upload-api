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

use Cloud\Model\User;
use Symfony\Component\HttpFoundation\Request;

/**
 * Get a user
 */
$app->get('/users/{user}', function(User $user) use ($app)
{
    return $app->json($user);
})
->assert('user', '\d+')
->convert('user', 'converter.user:convert')
// ->secure('ROLE_USER');
;

