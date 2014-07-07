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

namespace Cloud\Doctrine\Internal;

use Cloud\Doctrine\Exception;
use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Annotations\Reader as AnnotationReader;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectManagerAware;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\NotifyPropertyChanged;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;

abstract class AbstractEventSubscriber implements EventSubscriber
{
    /**
     * List of doctrine events this subscriber handles
     *
     * @var array
     */
    protected $subscribedEvents = [];

    /**
     * List of class level annotations this subscriber handles during metadata
     * load
     *
     * @see AbstractAnnotationEventSubscriber::configureClassAnnotation()
     * @var array
     */
    protected $classMetadataAnnotations = [];

    /**
     * List of field annotations this subscriber handles during metadata
     * load
     *
     * @see AbstractAnnotationEventSubscriber::configureFieldAnnotation()
     * @var array
     */
    protected $fieldMetadataAnnotations = [];

    /**
     * {@inheritDoc}
     */
    public function getSubscribedEvents()
    {
        return array_merge(['loadClassMetadata'], $this->subscribedEvents);
    }

    /**
     * Handle class and field annotations during metadata load
     *
     * @param  LoadClassMetadataEventArgs $e
     * @return void
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $e)
    {
        if (empty($this->classMetadataAnnotations) && empty($this->fieldMetadataAnnotations)) {
            return;
        }

        $om         = $e->getEntityManager();
        $metadata   = $e->getClassMetadata();
        $className  = $metadata->getName();
        $reflection = $metadata->getReflectionClass();
        $reader     = $this->getAnnotationReader($om, $metadata);

        // class annotations

        if (!empty($this->classMetadataAnnotations)) {
            foreach ($this->classMetadataAnnotations as $annotationName) {
                if ($annotation = $reader->getClassAnnotation($reflection, $annotationName)) {
                    $this->configureClassAnnotation($om, $metadata, $annotation);
                }
            }
        }

        // field annotations

        if (!empty($this->fieldMetadataAnnotations)) {
            foreach ($reflection->getProperties() as $property) {
                foreach ($this->fieldMetadataAnnotations as $annotationName) {
                    if ($annotation = $reader->getPropertyAnnotation($property, $annotationName)) {
                        $this->configureFieldAnnotation($om, $metadata, $property->getName(), $annotation);
                    }
                }
            }
        }
    }

    /**
     * Get the appropriate annotation reader for the given entity class from
     * Doctrine
     *
     * @param  ObjectManager $om
     * @param  ClassMetadata $metadata
     * @return AnnotationReader
     */
    protected function getAnnotationReader(ObjectManager $om, ClassMetadata $metadata)
    {
        $className        = $metadata->getName();
        $driverChain      = $om->getConfiguration()->getMetadataDriverImpl();
        $annotationReader = null;

        foreach ($driverChain->getDrivers() as $namespace => $driver) {
            if (strpos($className, $namespace) === 0) {
                $annotationReader = $driver->getReader();
            }
        }

        if (!$annotationReader) {
            $annotationReader = $driverChain->getDefaultReader();
        }

        if (!$annotationReader instanceof AnnotationReader) {
            throw new Exception\DomainException(sprintf(
                '%s(): Could not find doctrine annotation reader in driver chain for "%s"',
                __METHOD__, $metadata->name
            ));
        }

        return $annotationReader;
    }

    /**
     * Configure class level annotations of this event subscriber
     *
     * @see AbstractEventSubscriber::$classMetadataAnnotations
     * @param ObjectManager $om
     * @param ClassMetadata $metadata
     * @param object        $annotation
     * @return void
     */
    protected function configureClassAnnotation(ObjectManager $om, ClassMetadata $metadata, $annotation)
    {
        throw new Exception\DomainException(sprintf(
            'Event subscriber "%s" did not overwrite "%s()" but subscribed to annotations: "%s"',
            get_called_class(), __METHOD__,
            implode('", "', $this->classMetadataAnnotations)
        ));
    }

    /**
     * Configure field level annotations of this event subscriber
     *
     * @see AbstractEventSubscriber::$fieldMetadataAnnotations
     * @param ObjectManager $om
     * @param ClassMetadata $metadata
     * @param string        $fieldName
     * @param object        $annotation
     * @return void
     */
    protected function configureFieldAnnotation(ObjectManager $om, ClassMetadata $metadata, $fieldName, $annotation)
    {
        throw new Exception\DomainException(sprintf(
            'Event subscriber "%s" did not overwrite "%s()" but subscribed to annotations: "%s"',
            get_called_class(), __METHOD__,
            implode('", "', $this->fieldMetadataAnnotations)
        ));
    }

    /**
     * Create new entity instance and apply ObjectManagerAware
     *
     * @param  ClassMetadata $metadata
     * @return object
     */
    protected function newInstanceWithoutConstructor(ObjectManager $om, ClassMetadata $metadata)
    {
        $instance = $metadata->newInstance();

        if ($instance instanceof ObjectManagerAware) {
            $instance->injectObjectManager($om, $metadata);
        }

        return $instance;
    }

    /**
     * Create new entity instance through contructor and apply
     * ObjectManagerAware
     *
     * @param  ClassMetadata $metadata
     * @return object
     */
    protected function newInstanceArgs(ObjectManager $om, ClassMetadata $metadata, array $args)
    {
        $instance = $metadata->reflClass->newInstanceArgs($args);

        if ($instance instanceof ObjectManagerAware) {
            $instance->injectObjectManager($om, $metadata);
        }

        return $instance;
    }

    /**
     * Change the value of a field in an object and apply NotifyPropertyChanged
     *
     * @param  ObjectManager $om
     * @param  ClassMetadata $metadata
     * @param  object $object
     * @param  string $fieldName
     * @param  mixed $newValue
     * @return void
     */
    protected function setFieldValue(ObjectManager $om, ClassMetadata $metadata, $object, $fieldName, $newValue)
    {
        if ($object instanceof NotifyPropertyChanged) {
            $oldValue = $metadata->getFieldValue($object, $fieldName);
            $metadata->setFieldValue($object, $fieldName, $newValue);
            $om->getUnitOfWork()->propertyChanged($object, $fieldName, $oldValue, $newValue);
        } else {
            $metadata->setFieldValue($object, $fieldName, $newValue);
        }
    }
}
