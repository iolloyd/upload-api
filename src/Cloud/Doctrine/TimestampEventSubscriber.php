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
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\Event\LifecycleEventArgs;

class TimestampEventSubscriber extends AbstractEventSubscriber
{
    const FLAG_CREATED = 'cx:timestamp:createdAt';
    const FLAG_UPDATED = 'cx:timestamp:updatedAt';

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
        'Cloud\Doctrine\Annotation\CreatedAt',
        'Cloud\Doctrine\Annotation\UpdatedAt',
    ];

    /**
     * @param  LifecycleEventArgs $e
     * @return void
     */
    public function prePersist(LifecycleEventArgs $e)
    {
        $om = $e->getEntityManager();
        $object = $e->getEntity();

        $this->computeTimestamps($om, $object, true);
    }

    /**
     * @param  LifecycleEventArgs $e
     * @return void
     */
    public function preUpdate(/* PreUpdateEventArgs */ LifecycleEventArgs $e)
    {
        $om = $e->getEntityManager();
        $object = $e->getEntity();

        $this->computeTimestamps($om, $object, false);
    }

    /**
     * Compute timestamp fields for the given object
     *
     * @param  ObjectManager $om
     * @param  object $object
     * @param  bool $isCreated
     * @return void
     */
    protected function computeTimestamps(ObjectManager $om, $object, $isCreated)
    {
        $metadata = $om->getClassMetadata(get_class($object));

        if (!$this->hasMetadataClassFlag($metadata, self::FLAG_UPDATED)
            && ($isCreated && !$this->hasMetadataClassFlag($metadata, self::FLAG_CREATED))
        ) {
            return;
        }

        $now = new DateTime();

        foreach ($metadata->fieldMappings as $fieldName => $mapping) {
            if ($isCreated
                && $this->hasMetadataFieldFlag($metadata, $fieldName, self::FLAG_CREATED)
            ) {
                $value = $metadata->getFieldValue($object, $fieldName);

                if ($value !== null) {
                    // do not overwrite values which were set manually
                    continue;
                }

                $this->setFieldValue($om, $metadata, $object, $fieldName, $now);
            }

            if ($this->hasMetadataFieldFlag($metadata, $fieldName, self::FLAG_UPDATED)) {
                $this->setFieldValue($om, $metadata, $object, $fieldName, $now);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function configureFieldAnnotation(ObjectManager $om, ClassMetadata $metadata, $fieldName, $annotation)
    {
        if ($annotation instanceof Annotation\CreatedAt) {
            $this->setMetadataClassFlag($metadata, self::FLAG_CREATED);
            $this->setMetadataFieldFlag($metadata, $fieldName, self::FLAG_CREATED);
        }

        if ($annotation instanceof Annotation\UpdatedAt) {
            $this->setMetadataClassFlag($metadata, self::FLAG_UPDATED);
            $this->setMetadataFieldFlag($metadata, $fieldName, self::FLAG_UPDATED);
        }
    }
}
