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

namespace Cloud\Silex\Security;

use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns a `403 Forbidden` response on authentication errors. Used with our
 * API when a HTTPS endpoint is requested over HTTP.
 */
class ForbiddenErrorAuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    /**
     * {@inheritdoc}
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        $response = new JsonResponse();
        $response->setStatusCode(403);
        $response->setData([
            'error' => 'ssl_required',
            'error_description' => 'Requests to this resource must be SSL encrypted',
        ]);
        return $response;
    }
}
