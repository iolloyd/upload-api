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

use Cloud\Model\Site;
use Cloud\Model\TubesiteUser;
use Symfony\Component\HttpFoundation\Request;

/**
 * List sites of the logged in company
 */
$app->get('/sites', function (Request $request) use ($app)
{
    return $app['paginator.response.json']('cx:site', ['list', 'list.sites']);
});

/**
 * Get a site
 */
$app->get('/sites/{site}', function (Site $site) use ($app)
{
    return $app['single.response.json']($site, ['details', 'details.sites']);
})
->assert('site', '\d+')
->convert('site', 'converter.site:convert')
;

/**
 * List tubesite users of the given site
 */
$app->get('/sites/{site}/tubesite-users', function (Site $site, Request $request) use ($app)
{
    $tubeusers = $app['em']
        ->getRepository('cx:tubesiteUser')
        ->findBy(['site' => $site])
    ;

    return $app->json($app['serializer'](
        $tubeusers, ['list', 'list.tubesiteusers']
    ));
})
->assert('site', '\d+')
->convert('site', 'converter.site:convert')
;
