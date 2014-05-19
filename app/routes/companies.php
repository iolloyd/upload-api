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
$app->get('/companies/{company}', function(Company $company) use ($app)
{
    return $app->json($company);
})
->assert('company', '\d+')
->convert('company', 'converter.company:convert')
// ->secure('ROLE_USER');
;

