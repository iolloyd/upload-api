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
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\Security\Core\SecurityContext;

class IdentityEventSubscriber extends AbstractEventSubscriber
{
    const FLAG_CREATED = 'cx:identity:createdBy';
    const FLAG_COMPANY = 'cx:identity:company';

    /**
     * @var Application
     */
    protected $app;

    protected $security;

    /**
     * {@inheritDoc}
     */
    protected $subscribedEvents = [
        'prePersist',
    ];

    /**
     * {@inheritDoc}
     */
    protected $fieldMetadataAnnotations = [
        'Cloud\Doctrine\Annotation\CreatedBy',
        'Cloud\Doctrine\Annotation\Company',
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

        if (!$this->hasMetadataClassFlag($metadata, self::FLAG_CREATED)) {
            return;
        }

        foreach ($metadata->associationMappings as $fieldName => $mapping) {
            if ($this->hasMetadataFieldFlag($metadata, $fieldName, self::FLAG_CREATED)) {
                $value = $metadata->getFieldValue($object, $fieldName);

                if ($value !== null) {
                    // TODO Decide on the logic for different user
                }

                $user = $this->getSecurity()->getToken()->getUser();

                $this->setFieldValue($om, $metadata, $object, $fieldName, $user);
            }

            if ($this->hasMetadataFieldFlag($metadata, $fieldName, self::FLAG_COMPANY)) {
                $value = $metadata->getFieldValue($object, $fieldName);

                if ($value !== null) {
                    // TODO Decide on the logic for different company
                }

                $user = $this->getSecurity()->getToken()->getUser();
                $company = $user->getCompany();

                $this->setFieldValue($om, $metadata, $object, $fieldName, $company);
            }
        }
    }


    /**
     * {@inheritDoc}
     */
    protected function configureFieldAnnotation(ObjectManager $om, ClassMetadata $metadata, $fieldName, $annotation)
    {
        if ($annotation instanceof Annotation\CreatedBy) {
            $this->setMetadataClassFlag($metadata, self::FLAG_CREATED);
            $this->setMetadataFieldFlag($metadata, $fieldName, self::FLAG_CREATED);
        }

        if ($annotation instanceof Annotation\Company) {
            $this->setMetadataClassFlag($metadata, self::FLAG_COMPANY);
            $this->setMetadataFieldFlag($metadata, $fieldName, self::FLAG_COMPANY);
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
