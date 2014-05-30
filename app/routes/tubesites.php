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
$app->get('/tubesites/{tubesiteId}', function(Tubesite $tubesite) use ($app)
{
    $groups = ['list', 'details.tubesite',];
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
    $groups = ['list.tubesites', 'details.tubesites', 'list', 'stats'];
    $pagedView = $app['paginator.response.json']('cx:tubesite', $groups);
    return $pagedView;
});

/**
 * Create new draft tubesite
 */
$app->post('/tubesites', function(Request $request) use ($app)
{
    $tubesite = new Tubesite($app['user']);

    $app['em']->persist($tubesite);
    $app['em']->flush();

    $groups = ['list', 'details.tubesites',];

    return $app['single.response.json']($tubesite, $groups);
});


/**
 * Update a tubesite
 */
$app->post('/tubesites/{tubesite}', function(Tubesite $tubesite, Request $request) use ($app)
{
    $app['em']->transactional(function () use ($app, $tubesite, $request) {
        $tubesite->setTitle($request->get('title'));
        $tubesite->setDescription($request->get('description'));
        $tubesite->setFilename($request->get('filename'));
        $tubesite->setFilesize($request->get('filesize'));
        $tags = $app['converter.tags.from.request']($request->get('tags'));
        $tubesite->setTags($tags);
    });

    $groups = ['list', 'details.tubesites',];

    return $app['single.response.json']($tubesite, $groups);
})
    ->convert('tubesite', 'converter.tubesite:convert');

