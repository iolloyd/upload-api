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

use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Get the beta news feed
 */
$app->get('/beta/news', function (Request $request) use ($app)
{
    $client   = new Client();

    $response = $client->get('https://cloudxxx.squarespace.com/beta/news?format=RSS');
    $feed     = $response->xml();

    $num      = ((int) $request->get('num')) ?: 5;
    $entries  = [];

    foreach ($feed->channel->item as $item) {
        if (count($entries) >= $num) {
            break;
        }

        $entries[] = [
            'title'         => (string) $item->title,
            'link'          => (string) $item->link,
            'publishedDate' => (string) $item->pubDate,
            'content'       => (string) $item->description,
        ];
    }

    $response = new JsonResponse([
        'responseData' => [
            'feed' => [
                'entries' => $entries,
            ],
        ],
    ], 200);

    $response->setCallback($request->get('callback'));

    return $response;
});
