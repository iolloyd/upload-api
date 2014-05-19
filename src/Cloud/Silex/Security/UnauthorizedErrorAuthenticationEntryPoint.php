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
 * Returns a `401 Unauthorized` response on authentication errors. Used with
 * our API where no login form exists.
 */
class UnauthorizedErrorAuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    /**
     * {@inheritdoc}
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        $response = new JsonResponse();
        $response->setStatusCode(401);
        $response->setData([
            'error' => 'invalid_client',
            'error_description' => sprintf(
                'You are not authorized to access %s %s',
                $request->getMethod(), $request->getRequestUri()
            )
        ]);
        return $response;
    }
}
