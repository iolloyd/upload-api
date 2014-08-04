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

use Cloud\Silex\Security\AccessDeniedHandler;
use Cloud\Silex\Security\SslRequiredEntryPoint;
use Cloud\Silex\Security\InsufficientAuthenticationEntryPoint;
use Silex\Application;
use Silex\Provider\SecurityServiceProvider as BaseSecurityServiceProvider;
use Symfony\Component\Security\Core\AuthenticationEvents;
use Symfony\Component\Security\Core\Event\AuthenticationEvent;
use Symfony\Component\Security\Core\Encoder\BCryptPasswordEncoder;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationSuccessHandler;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationFailureHandler;
use Symfony\Component\Security\Http\Firewall\AccessListener;
use Symfony\Component\Security\Http\Firewall\AnonymousAuthenticationListener;
use Symfony\Component\Security\Http\Firewall\ContextListener;
use Symfony\Component\Security\Http\Firewall\ChannelListener;
use Symfony\Component\Security\Http\Firewall\ExceptionListener;

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
         *
         * @return \Cloud\Model\User|null
         */
        $app['user'] = function () use ($app) {
            if (null === $token = $app['security']->getToken()) {
                return null;
            }

            if (!$app['security']->isGranted('ROLE_USER')) {
                return null;
            }

            if (!is_object($user = $token->getUser())) {
                return null;
            }

            return $user;
        };

        /**
         * Returns the logged in company
         *
         * @return \Cloud\Model\Company|null
         */
        $app['company'] = function () use ($app) {
            if (null === $user = $app['user']) {
                return null;
            }

            if (!is_object($company = $user->getCompany())) {
                return null;
            }

            return $company;
        };

        // configuration

        $app['security.logger'] = function ($app) {
            return $app['logger'];
        };

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
                ['^/session$', 'IS_AUTHENTICATED_ANONYMOUSLY', $app['debug'] ? null : 'https'],
                ['^.*$',       'ROLE_USER',                    $app['debug'] ? null : 'https'],
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
                new SslRequiredEntryPoint(),
                $app['security.logger']
            );
        });

        $app['security.access_listener'] = $app->share(function ($app) {
            return new AccessListener(
                $app['security'],
                $app['security.access_manager'],
                $app['security.access_map'],
                $app['security.authentication_manager'],
                $app['security.logger']
            );
        });

        $app['security.context_listener._proto'] = $app->protect(function ($providerKey, $userProviders) use ($app) {
            return $app->share(function () use ($app, $userProviders, $providerKey) {
                return new ContextListener(
                    $app['security'],
                    $userProviders,
                    $providerKey,
                    $app['security.logger'],
                    $app['dispatcher']
                );
            });
        });

        $app['security.entry_point.default.form'] = $app->share(function () use ($app) {
            return new InsufficientAuthenticationEntryPoint();
        });

        $app['security.exception_listener._proto'] = $app->protect(function ($entryPoint, $name) use ($app) {
            return $app->share(function () use ($app, $entryPoint, $name) {
                return new ExceptionListener(
                    $app['security'],
                    $app['security.trust_resolver'],
                    $app['security.http_utils'],
                    $name,
                    $app[$entryPoint],
                    null, // errorPage
                    new AccessDeniedHandler(),
                    $app['security.logger']
                );
            });
        });

        $app['security.authentication.failure_handler._proto'] = $app->protect(function ($name, $options) use ($app) {
            return $app->share(function () use ($name, $options, $app) {
                return new DefaultAuthenticationFailureHandler(
                    $app,
                    $app['security.http_utils'],
                    $options,
                    $app['security.logger']
                );
            });
        });

        $app['security.authentication_listener.anonymous._proto'] = $app->protect(function ($providerKey, $options) use ($app) {
            return $app->share(function () use ($app, $providerKey, $options) {
                return new AnonymousAuthenticationListener(
                    $app['security'],
                    $providerKey,
                    $app['security.logger']
                );
            });
        });

        $app['security.encoder_factory'] = $app->share(function ($app) {
            return new EncoderFactory([
                'Cloud\Model\User' => new BCryptPasswordEncoder(12),
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
            foreach ($app['orm.ems.options'] as $name => $options) {
                $securityFilter = $app['orm.ems'][$name]->getFilters()->enable('security');

                if ($app['company']) {
                    $securityFilter->setParameter('company_id', $app['company']->getId());
                }
            }
        });

        // reset company on authentication change
        $app->on(AuthenticationEvents::AUTHENTICATION_SUCCESS, function (AuthenticationEvent $event) use ($app)
        {
            foreach ($app['orm.ems.options'] as $name => $options) {
                $companyId = $event
                    ->getAuthenticationToken()
                    ->getUser()
                    ->getCompany()
                    ->getId();

                $securityFilter = $app['orm.ems'][$name]->getFilters()->getFilter('security');
                $securityFilter->setParameter('company_id', $companyId);
            }
        });
    }
}


