<?php
/**
 * tubesite name
 * thumbnail
 * description
 * url
 *
 */

/**
 * cloudxxx-api (http://www.cloud.xxx)
 *
 * Copyright (C) 2014 Really Useful Limited.
 * Proprietary code. Usage restrictions apply.
 *
 * @copyright  Copyright (C) 2014 Really Useful Limited
 * @license    Proprietary
 */

use Cloud\Model\Tubesite;
use Symfony\Component\HttpFoundation\Request;

/**
 * Get a tubesite
 */
$app->get('/tubesites/{tubesite}', function(Tubesite $tubesite) use ($app)
{
    $groups = ['details.tubesites', 'list'];
    return $app['single.response.json']($tubesite, $groups);
})
    ->assert('tubesite', '\d+')
    ->convert('tubesite', 'converter.tubesite:convert')
;

/**
 * Get list of tubesites
 */
$app->get('/tubesites', function(Request $request) use ($app)
{
    $groups = ['details.tubesites', 'list'];
    $pagedView = $app['paginator.response.json']('cx:tubesite', $groups);
    return $pagedView;
});

