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

use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Returns a `403 Forbidden` response on access denied errors
 */
class AccessDeniedHandler implements AccessDeniedHandlerInterface
{
    /**
     * {@inheritDoc}
     */
    public function handle(Request $request, AccessDeniedException $accessDeniedException)
    {
        $response = new JsonResponse();

        $response->setStatusCode(403);
        $response->setData([
            'status' => 403,
            'title'  => 'Access Denied',
            'detail' => 'You do not have permission to access this URL',
        ]);

        return $response;
    }
}
