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

use Cloud\Monolog\Formatter\ConsoleFormatter;
use Cloud\Monolog\Formatter\LineFormatter;
use Cloud\Monolog\Handler\LogEntriesHandler;
use Cloud\Monolog\Processor\IntrospectionProcessor;
use Monolog\Logger;
use Monolog\Handler\GroupHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Processor\PsrLogMessageProcessor;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Silex\Provider\MonologServiceProvider as SilexMonologServiceProvider;
use Silex\EventListener\LogListener;
use Symfony\Bridge\Monolog\Handler\ConsoleHandler;
use Symfony\Bridge\Monolog\Handler\DebugHandler;
use Symfony\Bridge\Monolog\Processor\WebProcessor;
use Symfony\Component\HttpKernel\KernelEvents;

class LogServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function register(Application $app)
    {
        $app['logger'] = function ($app) {
            return $app['monolog']($app['monolog.name']);
        };

        /**
         * Monolog Channel Factory
         *
         *    $securityLogger = $app['monolog']('security');
         *    $workerLogger = $app['monolog']('worker');
         *
         *    // inherits configuration from security
         *    $childLogger = $app['monolog']('security.foo');
         *
         *    // global default
         *    $defaultLogger = $app['logger'];
         *
         */
        $app['monolog'] = $app->protect(function ($name) use ($app)
        {
            if (isset($app['monolog.loggers.' . $name])) {
                return $app['monolog.loggers.' . $name];
            }

            $parent = null;
            $path   = $name;

            /*
             * check if any of the following loggers exist:
             *
             *   foo.baz.bar
             *   foo.baz
             *   foo
             *
             */
            while (strstr($path, '.') !== false) {
                if (isset($app['monolog.loggers.' . $path])) {
                    $parent = $app['monolog.loggers.' . $path];
                    break;
                }

                list($path, ) = explode('.', $path);
            }

            if (!$parent) {
                $parent = $app['monolog.loggers.default'];
            }

            // create logger from parent

            $loggerClass = get_class($parent);

            return $app['monolog.loggers.' . $name] = new $loggerClass(
                $name,
                $parent->getHandlers(),
                $parent->getProcessors()
            );
        });

        // channels

        $app['monolog.channels'] = array_replace([
            'default' => [
                'handlers' => [
                    'file'       => Logger::DEBUG,
                    'console'    => Logger::NOTICE,
                    'logentries' => Logger::NOTICE,
                ],
                'processors' => [
                    'psr',
                    'pid',
                    'web',
                    'source',
                ],
            ],
            'api' => [
                'handlers' => [
                    'file'       => Logger::NOTICE,
                    'console'    => Logger::DEBUG,
                    'logentries' => Logger::NOTICE,
                ],
                'processors' => [
                    'psr',
                    'pid',
                    'web',
                    'source',
                ],
            ],
            'worker' => [
                'handlers' => [
                    'file'       => Logger::NOTICE,
                    'console'    => Logger::DEBUG,
                    'logentries' => Logger::NOTICE,
                ],
                'processors' => [
                    'psr',
                    'pid',
                    'web',
                    'source',
                ],
            ],
            'security' => [
                'handlers' => [
                    'file'          => Logger::NOTICE,
                    'console'       => Logger::DEBUG,
                    'logentries'    => Logger::NOTICE,
                    'security_file' => Logger::INFO,
                ],
                'processors' => [
                    'psr',
                    'pid',
                    'web',
                    'source',
                ],
            ],
        ], isset($app['monolog.channels']) ? $app['monolog.channels'] : []);

        // config

        $app['monolog.level'] = Logger::DEBUG;
        $app['monolog.name'] = 'default';
        $app['monolog.logfile'] = 'data/logs/cloud.log';
        $app['monolog.security.logfile'] = 'data/logs/security.log';

        $app['monolog.logger.class'] = 'Symfony\Bridge\Monolog\Logger';

        $app['monolog.translateLevel'] = $app->protect(function ($level = null) use ($app) {
            if ($level === null) {
                $level = $app['monolog.level'];
            }

            return SilexMonologServiceProvider::translateLevel($level);
        });

        // handlers

        $app['monolog.handler.file'] = $app->protect(function ($level = null) use ($app) {
            return new RotatingFileHandler(
                $app['monolog.logfile'],
                5,
                $app['monolog.translateLevel']($level)
            );
        });

        $app['monolog.handler.security_file'] = $app->protect(function ($level = null) use ($app) {
            return new RotatingFileHandler(
                $app['monolog.security.logfile'],
                5,
                $app['monolog.translateLevel']($level)
            );
        });

        $app['monolog.handler.logentries'] = $app->protect(function ($level = null) use ($app) {
            return new LogEntriesHandler(
                $app['config']['logentries']['token'],
                true,
                $app['monolog.translateLevel']($level)
            );
        });

        $app['monolog.handler.console'] = $app->protect(function ($level = null) use ($app) {
            $handler = new ConsoleHandler();

            if (php_sapi_name() == 'cli-server') {
                // FIXME: hack
                $handler->setOutput(new \Symfony\Component\Console\Output\ConsoleOutput(3, true));
            }

            foreach ($handler->getSubscribedEvents() as $eventName => $methodName) {
                $app->on($eventName, [$handler, $methodName]);
            }

            return $handler;
        });

        $app['monolog.handler.debug'] = $app->protect(function ($level = null) use ($app) {
            return new DebugHandler(
                $app['monolog.translateLevel']($level)
            );
        });

        // formatters

        $app['monolog.formatter.default'] = $app->share(function () use ($app) {
            return new LineFormatter("[%datetime%] %level_name% [%channel%] %message%\n  %extra%\n");
        });

        $app['monolog.formatter.logentries'] = $app->share(function () use ($app) {
            return new LineFormatter("%level_name% [%channel%] %message%\n  %extra%\n");
        });

        $app['monolog.formatter.console'] = $app->share(function () use ($app) {
            return new ConsoleFormatter();
        });

        // processors

        $app['monolog.processor.psr'] = $app->share(function () use ($app) {
            return new PsrLogMessageProcessor();
        });

        if (php_sapi_name() == 'cli') {
            $app['monolog.processor.pid'] = $app->share(function () use ($app) {
                return function (array $record) {
                    $record['extra']['pid'] = getmypid();
                    return $record;
                };
            });
        }

        $app['monolog.processor.web'] = $app->share(function () use ($app) {
            $processor = new WebProcessor();
            $app->on(KernelEvents::REQUEST, [$processor, 'onKernelRequest']);
            return $processor;
        });

        $app['monolog.processor.source'] = $app->share(function () use ($app) {
            return new IntrospectionProcessor($app['debug'] ? Logger::DEBUG : Logger::WARNING);
        });

        // configure logger for each channel

        foreach ($app['monolog.channels'] as $name => $options) {
            $app['monolog.loggers.' . $name] = $app->share(function ($app) use ($name)
            {
                $options = $app['monolog.channels'][$name];
                $logger  = new $app['monolog.logger.class']($name);

                // push handlers
                foreach ($options['handlers'] as $handler => $level) {
                    if (isset($app['monolog.handler.' . $handler])) {
                        $instance = $app['monolog.handler.' . $handler]($level);

                        if (isset($app['monolog.formatter.' . $handler])) {
                            $instance->setFormatter($app['monolog.formatter.' . $handler]);
                        } else {
                            $instance->setFormatter($app['monolog.formatter.default']);
                        }

                        $logger->pushHandler($instance);
                    }
                }

                if ($app['debug'] && isset($app['monolog.handler.debug'])) {
                    $logger->pushHandler($app['monolog.handler.debug']());
                }

                // push processors
                foreach ($options['processors'] as $processor) {
                    if (isset($app['monolog.processor.' . $processor])) {
                        $logger->pushProcessor($app['monolog.processor.' . $processor]);
                    }
                }

                return $logger;
            });
        }

        $app['monolog.listener'] = $app->share(function () use ($app) {
            return new LogListener($app['logger']);
        });
    }

    /**
     * {@inheritDoc}
     */
    public function boot(Application $app)
    {
        if (isset($app['monolog.listener'])) {
            $app['dispatcher']->addSubscriber($app['monolog.listener']);
        }
    }
}
