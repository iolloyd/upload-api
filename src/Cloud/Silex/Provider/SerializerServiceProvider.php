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

use Cloud\Serializer\Construction\DoctrineObjectConstructor;
use Cloud\Serializer\Construction\ContextObjectConstructor;
use JMS\Serializer\SerializerBuilder;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\Builder\CallbackDriverFactory;
use JMS\Serializer\Builder\DefaultDriverFactory;
use JMS\Serializer\Construction\UnserializeObjectConstructor;
use JMS\Serializer\EventDispatcher\EventDispatcher;
use JMS\Serializer\EventDispatcher\Subscriber\DoctrineProxySubscriber;
use JMS\Serializer\EventDispatcher\Subscriber\SymfonyValidatorSubscriber;
use JMS\Serializer\Metadata\Driver\DoctrineTypeDriver;
use Silex\Application;
use Silex\ServiceProviderInterface;

class SerializerServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function register(Application $app)
    {
        // metadata driver factories

        $app['serializer.metadata_driver_factory.default'] = $app->protect(function ($metadataDirs, $annotationReader) use ($app) {
            $factory = new DefaultDriverFactory();
            return $factory->createDriver($metadataDirs, $annotationReader);
        });

        $app['serializer.metadata_driver_factory.doctrine_orm'] = $app->protect(function ($metadataDirs, $annotationReader) use ($app) {
            return new DoctrineTypeDriver(
                $app['serializer.metadata_driver_factory.default']($metadataDirs, $annotationReader),
                $app['orm.manager_registry']
            );
        });

        $app['serializer.metadata_driver_factory'] = function ($app) {
            return $app['serializer.metadata_driver_factory.doctrine_orm'];
        };

        // serialization context

        $app['serializer.serialization_context'] = $app->protect(function (array $groups = null) use ($app) {
            $context = new SerializationContext();

            $context->setSerializeNull(true);

            if (isset($app['serializer.version'])) {
                $context->setVersion($app['serializer.version']);
            }

            if (!empty($app['serializer.max_depth_checks'])) {
                $context->enableMaxDepthChecks();
            }

            if ($groups) {
                $groups[] = 'Discriminator';
                $context->setGroups($groups);
            }

            return $context;
        });

        // deserialization context

        $app['serializer.deserialization_context'] = $app->protect(function () use ($app) {
            $context = new DeserializationContext();

            if (isset($app['serializer.version'])) {
                $context->setVersion($app['serializer.version']);
            }

            $context->setAttribute('validation_groups', ['Default']);

            return $context;
        });

        // serializer

        $app['serializer.builder'] = function ($app) {
            $builder = SerializerBuilder::create();

            $builder->setDebug($app['debug']);

            if (isset($app['serializer.cache_dir'])) {
                //$builder->setCacheDir($app['serializer.cache_dir']);
            }

            if (isset($app['serializer.metadata_driver_factory'])) {
                $builder->setMetadataDriverFactory(new CallbackDriverFactory(
                    $app['serializer.metadata_driver_factory']
                ));
            }

            $builder->configureListeners(function (EventDispatcher $dispatcher) use ($app)
            {
                $dispatcher->addSubscriber(new DoctrineProxySubscriber());

                if (isset($app['validator'])) {
                    $dispatcher->addSubscriber(new SymfonyValidatorSubscriber($app['validator']));
                }
            });

            $defaultConstructor  = new UnserializeObjectConstructor();
            $doctrineConstructor = new DoctrineObjectConstructor($app['orm.manager_registry'], $defaultConstructor);
            $contextConstructor  = new ContextObjectConstructor($doctrineConstructor);

            $builder->setObjectConstructor($contextConstructor);

            return $builder;
        };

        $app['serializer'] = $app->share(function ($app) {
            return $app['serializer.builder']->build();
        });
    }

    /**
     * {@inheritDoc}
     */
    public function boot(Application $app)
    {
    }
}
