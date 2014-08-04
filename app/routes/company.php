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
use Cloud\Model\Site;
use Symfony\Component\HttpFoundation\Request;

/**
 * Get the logged in company
 */
$app->get('/company', function (Request $request) use ($app)
{
    return $app->serialize($app->company(), ['details', 'details.company']);
});

/**
 * Update the logged in company
 */
$app->post('/company', function (Request $request) use ($app)
{
    $company = $app->company();

    $app['em']->transactional(function () use ($app, $site, $request) {
    });

    return $app->serialize($site, ['details', 'details.site']);
});

/**
 * Get a list of sites
 */
$app->get('/company/sites', function (Request $request) use ($app)
{
    return $app->serialize($app->company()->getSites(), ['list', 'list.sites']);
});

/**
 * Get a site
 */
$app->get('/company/sites/{site}', function (Site $site, Request $request) use ($app)
{
    return $app->serialize($site, ['details', 'details.site']);
})
->assert('site', '\d+')
->convert('site', 'converter.site.deserialize:convert')
;

/**
 * Update a site
 */
$app->post('/company/sites/{site}', function (Site $site, Request $request) use ($app)
{
    return $app->serialize($site, ['details', 'details.site']);
})
->assert('site', '\d+')
->convert('site', 'converter.site.deserialize:convert')
;

/**
 * List tubesite users of the given site
 */
$app->get('/company/sites/{site}/tubesite-users', function (Site $site, Request $request) use ($app)
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
