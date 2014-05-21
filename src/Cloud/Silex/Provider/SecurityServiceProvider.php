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

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Tools\Pagination\Paginator;
use JMS\Serializer\SerializerBuilder;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Adapter\DoctrineCollectionAdapter;
use Pagerfanta\Pagerfanta;
use Silex\Application;
use Silex\ServiceProviderInterface;

class SecurityServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function register(Application $app)
    {
        $app['security.users'] = $app->share(function () use ($app) {
            return $app['em']->getRepository('cx:user');
        });
        $app->register(new \Silex\Provider\SecurityServiceProvider(), [
            'security.firewalls' => [
                'default' => [
                    'pattern'   => '^.*$',
                    'users'     => $app['security.users'],
                    'form'      => [
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
                return new \Symfony\Component\Security\Http\Firewall\ChannelListener(
                    $app['security.access_map'],
                    new \Cloud\Silex\Security\ForbiddenErrorAuthenticationEntryPoint(),
                    $app['logger']
                );
            }),
            'security.entry_point.default.form' => $app->share(function () use ($app) {
                return new \Cloud\Silex\Security\UnauthorizedErrorAuthenticationEntryPoint();
            }),
            'security.encoder_factory' => $app->share(function ($app) {
                return new \Symfony\Component\Security\Core\Encoder\EncoderFactory(array(
                    'Cloud\Model\User' => new \Symfony\Component\Security\Core\Encoder\BCryptPasswordEncoder(10),
                ));
            }),
        ]);

    }

    /**
     * {@inheritDoc}
     */
    public function boot(\Silex\Application $app)
    {
    }
}


