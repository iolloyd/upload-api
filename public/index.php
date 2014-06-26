<?php

use Cloud\Silex\Provider\CorsHeadersServiceProvider;
use Cloud\Silex\Provider\DoctrinePaginatorServiceProvider;
use Cloud\Silex\Security\ForbiddenErrorAuthenticationEntryPoint;
use Cloud\Silex\Security\UnauthorizedErrorAuthenticationEntryPoint;
use Silex\Provider\SecurityServiceProvider;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;
use Symfony\Component\Security\Http\Firewall\ChannelListener;
use Symfony\Component\Security\Core\Encoder\BCryptPasswordEncoder;

ini_set('display_errors', true);
error_reporting(E_ALL);
set_time_limit(0);
date_default_timezone_set('UTC');

chdir(dirname(__DIR__));

// Decline static file requests back to the PHP built-in webserver
if (php_sapi_name() === 'cli-server'
    && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))
) {
    return false;
}

require 'autoload.php';
require 'bootstrap.php';

// security
// TODO: move to Cloud\Silex\Provider\SecurityServiceProvider
$app['user'] = $app->share(function () use ($app) {
    $token = $app['security']->getToken();

    if ($token && $app['security']->isGranted('ROLE_USER')) {
        return $token->getUser();
    }

    return null;
});
$app['security.users'] = $app->share(function () use ($app) {
    return $app['em']->getRepository('cx:user');
});

$app->register(new SecurityServiceProvider(), [
    'security.firewalls' => [
        'default' => [
            'pattern' => '^.*$',
            'users' => $app['security.users'],
            'form' => [
                'login_path'                     => '/session',
                'default_target_path'            => '/session',
                'failure_path'                   => '/session/failure',
                'check_path'                     => '/_session_check',
                'logout_path'                    => '/_session_logout',
                'always_use_default_target_path' => true,
                'use_forward'                    => true,
                'failure_forward'                => true,
                'require_previous_session'       => false,
            ],
            'logout' => [
                'logout_path' => '/_session_logout',
            ],
            'anonymous' => true,
        ],
    ],
    'security.access_rules' => [
        ['^/session$', 'IS_AUTHENTICATED_ANONYMOUSLY', $app['debug'] ? 'http' : 'https'],
        ['^.*$',       'ROLE_USER',                    $app['debug'] ? 'http' : 'https'],
    ],
    'security.role_hierarchy' => [
        'ROLE_USER'  => [],
        'ROLE_ADMIN' => ['ROLE_USER'],
    ],
    'security.hide_user_not_found' => !$app['debug'],

    'security.channel_listener' => $app->share(function ($app) {
        return new ChannelListener(
            $app['security.access_map'],
            new ForbiddenErrorAuthenticationEntryPoint(),
            $app['logger.security']
        );
    }),
    'security.entry_point.default.form' => $app->share(function () use ($app) {
        return new UnauthorizedErrorAuthenticationEntryPoint();
    }),
    'security.encoder_factory' => $app->share(function ($app) {
        return new EncoderFactory([
            'Cloud\Model\User' => new BCryptPasswordEncoder(10),
            ]);
    }),
]);

// providers
$app->register(new SessionServiceProvider(), [
    'session.storage.options' => [
        'name'                    => 'CLOUD',
        'hash_function'           => 'sha256',
        'hash_bits_per_character' => 6,
        'cookie_lifetime'         => $app['debug'] ? 0 : 3600*24*6,
        'cookie_secure'           => !$app['debug'],
        'cookie_httponly'         => true,
    ],
]);
$app->register(new CorsHeadersServiceProvider(), [
    'cors.options' => [
        'allow_credentials' => true,
        'allow_origin'      => $app['debug'] ? null : 'https://ng.staging.cldsys.net',
        'max_age'           => 604800,
    ],
]);
$app->register(new UrlGeneratorServiceProvider());
$app->register(new DoctrinePaginatorServiceProvider());

// json request parser
$app->before(function ($request) {
    if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
        $data = json_decode($request->getContent(), true);
        $request->request->replace(is_array($data) ? $data : []);
    }
});

// run
$app->boot();
$app['load']('routes');

$app->run();
