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

namespace Cloud\Silex\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsHeadersServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function register(Application $app)
    {
        $app['cors.default_options'] = [
            'allow_methods'     => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
            'allow_headers'     => ['Content-Type', 'X-XSRF-TOKEN'],
            'allow_credentials' => false,
            'allow_origin'      => null,
            'max_age'           => 3600,
            'expose_headers'    => ['Link'],
        ];

        $app['cors.options.initializer'] = $app->protect(function () use ($app) {
            static $initialized = false;

            if ($initialized) {
                return;
            }

            $initialized = true;

            if (!isset($app['cors.options'])) {
                $app['cors.options'] = [];
            }

            $app['cors.options'] = array_replace($app['cors.default_options'], $app['cors.options']);
        });

        $app['cors.middleware.before'] = $app->protect(function (Request $request) use ($app) {
            if ($request->isMethod('OPTIONS') && $request->headers->has('Access-Control-Request-Method')) {
                $response = new Response();
                $response->setStatusCode(204);
                return $response; // always stop and send empty response for OPTIONS requests
            }
        });

        $app['cors.middleware.after'] = $app->protect(function (Request $request, Response $response) use ($app) {
            $app['cors.options.initializer']();

            if (!$request->headers->has('Origin')) {
                return; // not CORS
            }

            $options = $app['cors.options'];

            $response->headers->set('Access-Control-Allow-Methods', implode(', ', $options['allow_methods']));

            if (count($options['allow_headers'])) {
                $response->headers->set('Access-Control-Allow-Headers', implode(', ', $options['allow_headers']));
            }

            if ($options['allow_credentials']) {
                $response->headers->set('Access-Control-Allow-Credentials', 'true');
            }

            if ($options['allow_origin']) {
                $allowOrigin = is_array($options['allow_origin']) ? $options['allow_origin'] : [$options['allow_origin']];
                $response->headers->set('Access-Control-Allow-Origin', implode(' ', $allowOrigin));
            } elseif ($app['env'] == 'development') {
                $response->headers->set('Access-Control-Allow-Origin', $request->headers->get('Origin'));
            }

            if ($options['max_age']) {
                $response->headers->set('Access-Control-Max-Age', $options['max_age']);
            }

            if (count($options['expose_headers'])) {
                $response->headers->set('Access-Control-Expose-Headers', implode(', ', $options['expose_headers']));
            }

            $response->headers->set('Vary', 'Origin, Access-Control-Request-Headers, Access-Control-Request-Method, X-XSRF-TOKEN');
        });
    }

    /**
     * {@inheritDoc}
     */
    public function boot(Application $app)
    {
        $app->before($app['cors.middleware.before'], Application::EARLY_EVENT);
        $app->after($app['cors.middleware.after']);
    }
}
