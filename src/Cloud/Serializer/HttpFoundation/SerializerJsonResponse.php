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

namespace Cloud\Serializer\HttpFoundation;

use JMS\Serializer\SerializerInterface;
use JMS\Serializer\SerializationContext;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * A HTTP response with content serialized with JMS Serializer
 */
class SerializerJsonResponse extends JsonResponse
{
    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * Constructor
     *
     * @param SerializerInterface  $serializer
     * @param SerializationContext $context
     * @param object|array|scalar  $data
     * @param int                  $status
     * @param array                $headers
     */
    public function __construct(SerializerInterface $serializer, SerializationContext $context = null, $data = null, $status = 200, array $headers = [])
    {
        Response::__construct('', $status, $headers);

        if (null === $data) {
            $data = new \ArrayObject();
        }

        $this
            ->setSerializer($serializer)
            ->setData($data, $context);
    }

    /**
     * Set the serializer instance to generate the response with
     *
     * @param  SerializerInterface $serializer
     *
     * @return SerializerJsonResponse
     */
    public function setSerializer(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
        return $this;
    }

    /**
     * Get the serializer instance to generate the response with
     *
     * @return SerializerInterface
     */
    public function getSerializer()
    {
        return $this->serializer;
    }

    /**
     * Sets the data to be sent as JSON.
     *
     * @param object|array|scalar  $data
     * @param SerializationContext $context
     *
     * @return SerializerJsonResponse
     */
    public function setData($data = array(), SerializationContext $context = null)
    {
        if (!$this->serializer) {
            throw new \LogicException('SerializerJsonResponse requires a serializer to be set before calling setData()');
        }

        $this->data = $this->serializer->serialize(
            $data,
            'json',
            $context
        );

        return $this->update();
    }
}
