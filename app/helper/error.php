<?php

use JMS\Serializer\Exception\ValidationFailedException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * 404 Not Found
 */
$app->error(function (NotFoundHttpException $e, $code) use ($app)
{
    return $app->json([
        'status' => 404,
        'title'  => 'Not Found',
        'detail' => $app['debug'] ? $e->getMessage() : null,
    ], 404);
});

/**
 * 405 Method Not Allowed
 */
$app->error(function (MethodNotAllowedHttpException $e, $code) use ($app)
{
    return $app->json([
        'status' => 405,
        'title'  => 'Method Not Allowed',
        'detail' => $app['debug'] ? $e->getMessage() : null,
    ], 405);
});

/**
 * 422 Validation Failed
 */
$app->error(function (ValidationFailedException $e, $code) use ($app)
{
    $fields = [];

    foreach ($e->getConstraintViolationList() as $violation) {
        $fields[$violation->getPropertyPath()] = $violation->getMessage();
    }

    return $app->json([
        'status' => 422,
        'title'  => 'Validation Failed',
        'detail' => $e->getMessage(),
        'fields' => $fields,
    ], 422);
});
