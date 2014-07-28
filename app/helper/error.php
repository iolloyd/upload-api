<?php

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

$app->error(function (NotFoundHttpException $e, $code) use ($app)
{
    return $app->json([
        'status' => 404,
        'title'  => 'Not Found',
        'detail' => $e->getMessage(),
    ], 404);
});
