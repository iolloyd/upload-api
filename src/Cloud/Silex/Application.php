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

namespace Cloud\Silex;

use Cloud\Serializer\HttpFoundation\SerializerJsonResponse;
use Cloud\Silex\Application\SecurityTrait;
use Silex\Application as BaseApplication;
use Silex\Application\MonologTrait;

class Application extends BaseApplication
{
    use SecurityTrait;

    /**
     * Create a JSON response from the given data using the serializer
     *
     * @param mixed   $data    The response data
     * @param array   $groups  Exclusion groups allowed in the response
     * @param integer $status  The response status code
     * @param array   $headers An array of response headers
     *
     * @return JsonResponse
     */
    public function serialize($data = null, array $groups = null, $status = 200, array $headers = [])
    {
        $serializer = $this['serializer'];
        $context    = $this['serializer.context._factory']($groups);

        return new SerializerJsonResponse($serializer, $context, $data, $status, $headers);
    }
}
