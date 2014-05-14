<?php

use Symfony\Component\HttpFoundation\Response;

$app->error(function (\Exception $e, $code) use ($app)
{
    switch ($code) {
        case 404:
            $message = [404, "Request could not be found"];
            break;
        default:
            $message = [500, "Something went terribly wrong"];
    }

    return $app->json($message);
});
