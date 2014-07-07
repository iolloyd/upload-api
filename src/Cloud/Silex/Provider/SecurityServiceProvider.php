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

use Cloud\Silex\Security\ForbiddenErrorAuthenticationEntryPoint;
use Cloud\Silex\Security\UnauthorizedErrorAuthenticationEntryPoint;
use Silex\Application;
use Silex\Provider\SecurityServiceProvider as BaseSecurityServiceProvider;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;
use Symfony\Component\Security\Http\Firewall\ChannelListener;
use Symfony\Component\Security\Core\Encoder\BCryptPasswordEncoder;

class SecurityServiceProvider extends BaseSecurityServiceProvider
{
    /**
     * {@inheritDoc}
     */
    public function register(Application $app)
    {
        parent::register($app);

        /**
         * Returns the logged in user
         */
        $app['user'] = $app->share(function () use ($app) {
            $token = $app['security']->getToken();

            if ($token && $app['security']->isGranted('ROLE_USER')) {
                return $token->getUser();
            }

            return null;
        });

        /**
         * Returns the logged in company
         */
        $app['company'] = $app->share(function () use ($app) {
            $user = $app['user'];

            if ($user) {
                return $user->getCompany();
            }

            return null;
        });

        // configuration

        $app['security.users'] = $app->share(function () use ($app) {
            return $app['em']->getRepository('Cloud\Model\User');
        });

        $app['security.firewalls'] = $app->share(function () use ($app) {
            return [
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
            ];
        });

        $app['security.access_rules'] = $app->share(function () use ($app) {
            return [
                ['^/session$', 'IS_AUTHENTICATED_ANONYMOUSLY', $app['debug'] ? 'http' : 'https'],
                ['^.*$',       'ROLE_USER',                    $app['debug'] ? 'http' : 'https'],
            ];
        });

        $app['security.role_hierarchy'] = $app->share(function () use ($app) {
            return [
                'ROLE_USER'  => [],
                'ROLE_ADMIN' => ['ROLE_USER'],
            ];
        });

        $app['security.hide_user_not_found'] = $app->share(function () use ($app) {
            return !$app['debug'];
        });

        $app['security.channel_listener'] = $app->share(function ($app) {
            return new ChannelListener(
                $app['security.access_map'],
                new ForbiddenErrorAuthenticationEntryPoint(),
                $app['logger.security']
            );
        });

        $app['security.entry_point.default.form'] = $app->share(function () use ($app) {
            return new UnauthorizedErrorAuthenticationEntryPoint();
        });

        $app['security.encoder_factory'] = $app->share(function ($app) {
            return new EncoderFactory([
                'Cloud\Model\User' => new BCryptPasswordEncoder(10),
            ]);
        });
    }

    /**
     * {@inheritDoc}
     */
    public function boot(Application $app)
    {
        parent::boot($app);

        // enable sql filter
        $app->before(function () use ($app) {
            if (!$app['company']) {
                return;
            }

            foreach ($app['orm.ems.options'] as $name => $options) {
                $securityFilter = $app['orm.ems'][$name]->getFilters()->enable('security');
                $securityFilter->setParameter('company_id', $app['company']->getId());
            }
        });
    }
}


