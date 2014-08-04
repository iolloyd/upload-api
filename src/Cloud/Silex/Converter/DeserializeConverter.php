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

namespace Cloud\Silex\Converter;

use JMS\Serializer\SerializerInterface;
use JMS\Serializer\DeserializationContext;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class DeserializeConverter
{
    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var string
     */
    protected $entityName;

    /**
     * Constructor
     *
     * @param  SerializerInterface $serializer
     * @param  string              $className
     */
    public function __construct(SerializerInterface $serializer, $className)
    {
        $this->serializer = $serializer;
        $this->className = $className;
    }

    /**
     * Convert parameter to ORM entity
     *
     * @param  int $id  entity identifier
     * @return object
     */
    public function convert($id)
    {
        if (!is_numeric($id)) {
            throw new BadRequestHttpException('Identifier must be numeric');
        }

        $context = new DeserializationContext();
        $context->setAttribute('validation_groups', ['Default']);

        return $this->serializer->deserialize(
            $id,
            $this->className,
            'json',
            $context
        );
    }
}
