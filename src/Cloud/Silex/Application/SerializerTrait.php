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

namespace Cloud\Silex\Application;

use InvalidArgumentException;
use Cloud\Serializer\HttpFoundation\Response;
use Cloud\Serializer\HttpFoundation\SerializerJsonResponse;
use Symfony\Component\HttpFoundation\Request;

trait SerializerTrait
{
    /**
     * Create a JSON response from the given data using the serializer
     *
     * @param mixed   $data    The response data
     * @param array   $groups  Exclusion groups allowed in the response
     * @param integer $status  The response status code
     * @param array   $headers An array of response headers
     *
     * @return Response
     */
    public function serialize($data, array $groups = null, $status = 200, array $headers = [])
    {
        $serializer = $this['serializer'];
        $context    = $this['serializer.serialization_context']($groups);

        return new SerializerJsonResponse($serializer, $context, $data, $status, $headers);
    }

    /**
     * Deserialize the given data using the serializer
     *
     * @param mixed         $data    serialized data
     * @param string|object $type    type of the deserialized data
     * @param string|null   $format  format of the serialized data
     *
     * @return mixed
     */
    public function deserialize($data, $type, $format = null)
    {
        $serializer = $this['serializer'];
        $context    = $this['serializer.deserialization_context']();

        if (is_object($type)) {
            $object = clone $type;
            $type = get_class($object);
            $context->setAttribute('object', $object);
        }

        if ($format) {
            $format = $format;
        } elseif (is_array($data)) {
            $format = 'raw';
        } elseif (is_string($data)) {
            $format = 'json';
        } elseif ($data instanceof Request) {
            $data = $data->request->all();
            $format = 'raw';
        } else {
            throw new InvalidArgumentException(sprintf(
                'Cannot auto-detect format for data of type `%s`',
                is_object($data) ? get_class($data) : gettype($data)
            ));
        }

        return $serializer->deserialize($data, $type, $format, $context);
    }
}

