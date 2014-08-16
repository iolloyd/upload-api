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
use Cloud\Model\User;
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

    $company = $app->transactional(function ($em) use ($app, $request, $company) {
        return $em->merge($app->deserialize($request, $company));
    });

    return $app->serialize($company, ['details', 'details.company']);
});

//////////////////////////////////////////////////////////////////////////////

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
->convert('site', 'converter.site:convert')
;

/**
 * Update a site
 */
$app->post('/company/sites/{site}', function (Site $site, Request $request) use ($app)
{
    $site = $app->transactional(function ($em) use ($app, $request, $site) {
        return $em->merge($app->deserialize($request, $site));
    });

    return $app->serialize($site, ['details', 'details.site']);
})
->assert('site', '\d+')
->convert('site', 'converter.site:convert')
;

//////////////////////////////////////////////////////////////////////////////

/**
 * List tubesite users of the given site
 */
$app->get('/company/sites/{site}/tubesite-users', function (Site $site, Request $request) use ($app)
{
    $tubeusers = $app['em']
        ->getRepository('cx:tubesiteUser')
        ->findBy(['site' => $site])
    ;

    return $app->serialize($tubeusers, ['list', 'list.tubesiteusers']);
})
->assert('site', '\d+')
->convert('site', 'converter.site:convert')
;

//////////////////////////////////////////////////////////////////////////////

/**
 * Get the logged in user
 */
$app->get('/company/user', function (Request $request) use ($app)
{
    return $app->serialize($app->user(), ['details', 'details.user']);
});

/**
 * Update the logged in user
 */
$app->post('/company/user', function (Request $request) use ($app)
{
    $user = $app->user();

    $user = $app->transactional(function ($em) use ($app, $request, $user) {
        return $em->merge($app->deserialize($request, $user));
    });

    return $app->serialize($user, ['details', 'details.user']);
});

//////////////////////////////////////////////////////////////////////////////

/**
 * Get a list of users
 */
$app->get('/company/users', function (Request $request) use ($app)
{
    return $app->serialize($app->company()->getUsers(), ['list', 'list.users']);
});

/**
 * Get a user
 */
$app->get('/company/users/{user}', function (User $user, Request $request) use ($app)
{
    return $app->serialize($user, ['details', 'details.user']);
})
->assert('user', '\d+')
->convert('user', 'converter.user:convert')
;
