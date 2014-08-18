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

use JMS\Serializer\DeserializationContext;
use JMS\Serializer\VisitorInterface;
use JMS\Serializer\Exception\InvalidArgumentException;
use JMS\Serializer\Exception\LogicException;
use JMS\Serializer\Metadata\ClassMetadata;

/**
 * Object constructor that returns a given object instance from the
 * deserialization context. This is used when merging data into an existing
 * object without constructing a new empty one.
 */
class ContextObjectConstructor extends AbstractConstructorDecorator
{
    /**
     * {@inheritdoc}
     */
    public function construct(VisitorInterface $visitor, ClassMetadata $metadata, $data, array $type, DeserializationContext $context)
    {
        if (!$context->attributes->containsKey('object')) {
            return $this->delegateConstructor->construct($visitor, $metadata, $data, $type, $context);
        }

        $object = $context->attributes->get('object')->get();

        if (!$object instanceof $type['name']) {
            throw new InvalidArgumentException(sprintf(
                'DeserializationContext object of class `%s` does not '
                . 'match expected class `%s`',
                get_class($object),
                $type['name']
            ));
        }

        return $object;
    }
}
