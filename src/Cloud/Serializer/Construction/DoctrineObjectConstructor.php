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

namespace Cloud\Serializer\Construction;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\VisitorInterface;
use JMS\Serializer\Construction\ObjectConstructorInterface;
use JMS\Serializer\Exception\InvalidArgumentException;
use JMS\Serializer\Exception\LogicException;
use JMS\Serializer\Metadata\ClassMetadata;

/**
 * Extended Doctrine object constructor which allows to explicitly specify the
 * id as a context attribute
 */
class DoctrineObjectConstructor extends AbstractConstructorDecorator
{
    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * Constructor
     *
     * @param ManagerRegistry            $managerRegistry
     * @param ObjectConstructorInterface $delegateConstructor
     */
    public function __construct(ManagerRegistry $managerRegistry, ObjectConstructorInterface $delegateConstructor)
    {
        parent::__construct($delegateConstructor);
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function construct(VisitorInterface $visitor, ClassMetadata $metadata, $data, array $type, DeserializationContext $context)
    {
        if (null === $objectManager = $this->getObjectManager($metadata)) {
            return $this->delegateConstructor->construct($visitor, $metadata, $data, $type, $context);
        }

        if ($context->attributes->containsKey('id')) {
            $params = $context->attributes->get('id')->get();
        } else {
            $params = $data;
        }

        if (null === $identifiers = $this->getIdentifier($objectManager, $metadata, $params)) {
            return $this->delegateConstructor->construct($visitor, $metadata, $data, $type, $context);
        }

        $object = $objectManager->find($metadata->name, $identifiers);

        if (!$object) {
            throw new InvalidArgumentException(sprintf(
                '%s ( %s ) does not exist',
                $metadata->name,
                json_encode($identifiers)
            ));

            // return $objectManager->getPartialReference($metadata->name, $identifiers);
        }

        $objectManager->detach($object);

        return $object;
    }

    /**
     * Try to load the Doctrine object manager for the given class or
     * return null if it doesn't exist
     *
     * @param ClassMetadata $metadata  serializer metadata
     *
     * @return DoctrineObjectManager|null
     */
    protected function getObjectManager(ClassMetadata $metadata)
    {
        $objectManager = $this->managerRegistry->getManagerForClass($metadata->name);

        if (!$objectManager) {
            return null;
        }

        $classMetadataFactory = $objectManager->getMetadataFactory();

        if ($classMetadataFactory->isTransient($metadata->name)) {
            return null;
        }

        return $objectManager;
    }

    /**
     * Extract identifier values from the given database
     *
     * The data can either be a single scalar identifier or an array of values
     * including the identifier fields.
     *
     * If the given data does not contain the identifiers, null is returned.
     *
     * @param ObjectManager $objectManager
     * @param ClassMetadata $metadata
     * @param array|scalar  $data
     *
     * @return array|null
     */
    protected function getIdentifier(ObjectManager $objectManager, ClassMetadata $metadata, $data)
    {
        $classMetadata    = $objectManager->getClassMetadata($metadata->name);
        $identifierFields = $classMetadata->getIdentifierFieldNames();

        if (is_array($data)) {
            $identifiers = array_intersect_key($data, array_flip($identifierFields));
        } elseif (count($identifierFields) === 1) {
            $identifiers = [ $identifierFields[0] => $data ];
        } else {
            $identifiers = [];
        }

        if (count($identifiers) !== count($identifierFields)) {
            return null;
        }

        return $identifiers;
    }
}
