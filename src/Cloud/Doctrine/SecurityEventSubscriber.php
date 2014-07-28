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

namespace Cloud\Doctrine;

use DateTime;
use Cloud\Silex\Application;
use Cloud\Doctrine\Internal\AbstractEventSubscriber;
use Cloud\Doctrine\Internal\ClassMetadataUtils as Utils;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\Security\Core\SecurityContext;

class SecurityEventSubscriber extends AbstractEventSubscriber
{
    const FLAG_COMPANY         = 'cx:security:company';
    const FLAG_CREATED_BY      = 'cx:security:createdBy';
    const FLAG_UPDATED_BY      = 'cx:security:updatedBy';
    const FLAG_ALLOW_ANONYMOUS = 'cx:security:allowAnonymous';

    /**
     * @var Application
     */
    protected $app;

    /**
     * @var SecurityContext
     */
    protected $security;

    /**
     * {@inheritDoc}
     */
    protected $subscribedEvents = [
        'prePersist',
        'preUpdate',
    ];

    /**
     * {@inheritDoc}
     */
    protected $fieldMetadataAnnotations = [
        'Cloud\Doctrine\Annotation\Company',
        'Cloud\Doctrine\Annotation\CreatedBy',
        'Cloud\Doctrine\Annotation\UpdatedBy',
    ];

    /**
     * Constructor
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @param  LifecycleEventArgs $e
     * @return void
     */
    public function prePersist(LifecycleEventArgs $e)
    {
        $om = $e->getEntityManager();
        $object = $e->getEntity();

        $metadata = $om->getClassMetadata(get_class($object));

        if (!Utils::hasMetadataClassFlag($metadata, self::FLAG_CREATED_BY)
            && !Utils::hasMetadataClassFlag($metadata, self::FLAG_UPDATED_BY)
            && !Utils::hasMetadataClassFlag($metadata, self::FLAG_COMPANY)
        ) {
            return;
        }

        foreach ($metadata->associationMappings as $fieldName => $mapping) {
            if (Utils::hasMetadataFieldFlag($metadata, $fieldName, self::FLAG_COMPANY)) {
                $value = $metadata->getFieldValue($object, $fieldName);

                if ($value !== null) {
                    // do not overwrite values which were set manually
                    continue;
                }

                $this->setFieldValue($om, $metadata, $object, $fieldName, $this->app['company']);
            }

            if (Utils::hasMetadataFieldFlag($metadata, $fieldName, self::FLAG_CREATED_BY)) {
                $value = $metadata->getFieldValue($object, $fieldName);

                if ($value !== null) {
                    // do not overwrite values which were set manually
                    continue;
                }

                $this->setFieldValue($om, $metadata, $object, $fieldName, $this->app['user']);
            }

            if (Utils::hasMetadataFieldFlag($metadata, $fieldName, self::FLAG_UPDATED_BY)) {
                $this->setFieldValue($om, $metadata, $object, $fieldName, $this->app['user']);
            }
        }
    }

    /**
     * @param  LifecycleEventArgs $e
     * @return void
     */
    public function preUpdate(/* PreUpdateEventArgs */ LifecycleEventArgs $e)
    {
        $om = $e->getEntityManager();
        $object = $e->getEntity();

        $metadata = $om->getClassMetadata(get_class($object));

        if (!Utils::hasMetadataClassFlag($metadata, self::FLAG_UPDATED_BY)) {
            return;
        }

        foreach ($metadata->associationMappings as $fieldName => $mapping) {
            if (Utils::hasMetadataFieldFlag($metadata, $fieldName, self::FLAG_UPDATED_BY)) {
                $this->setFieldValue($om, $metadata, $object, $fieldName, $this->app['user']);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function configureFieldAnnotation(ObjectManager $om, ClassMetadata $metadata, $fieldName, $annotation)
    {
        if ($annotation instanceof Annotation\Company) {
            Utils::setMetadataClassFlag($metadata, self::FLAG_COMPANY);
            Utils::setMetadataFieldFlag($metadata, $fieldName, self::FLAG_COMPANY);
            Utils::setMetadataFieldFlag($metadata, $fieldName, self::FLAG_ALLOW_ANONYMOUS, $annotation->allowAnonymous);
        }

        if ($annotation instanceof Annotation\CreatedBy) {
            Utils::setMetadataClassFlag($metadata, self::FLAG_CREATED_BY);
            Utils::setMetadataFieldFlag($metadata, $fieldName, self::FLAG_CREATED_BY);
        }

        if ($annotation instanceof Annotation\UpdatedBy) {
            Utils::setMetadataClassFlag($metadata, self::FLAG_UPDATED_BY);
            Utils::setMetadataFieldFlag($metadata, $fieldName, self::FLAG_UPDATED_BY);
        }
    }

    /**
     * Gets the app's security context
     *
     * @return SecurityContext
     */
    protected function getSecurity()
    {
        if (!$this->security) {
            $this->security = $this->app['security'];
        }

        return $this->security;
    }
}
