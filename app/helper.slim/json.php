<?php
/**
 * JSON Utilities
 */

/**
 * Get a value from the request body
 */
$param = function ($key, $default = null) use ($app)
{
    $data = $app->request->getBody();

    if (is_array($data) && isset($data[$key])) {
        return $data[$key];
    }

    return $default;
};

/**
 * Send a JSON response
 */
$json = function ($statusOrData, $data = null) use ($app)
{
    if ($data) {
        $app->response->status($statusOrData);
        $statusOrData = $data;
    }

    $app->response->headers->set('Content-Type', 'application/json');

    /*
     * Prefix JSON output with following string: ")]}',\n"
     * See https://docs.angularjs.org/api/ng/service/$http#json-vulnerability-protection
     */
    $app->response->body(
        ")]}',\n" . json_encode($statusOrData)
    );
};

/**
 * Send a JSON error response
 */
$jsonError = function ($status, $error = null, $errorDescription = null) use ($app)
{
    $app->json($status, [
        'error' => $error,
        'error_description' => $errorDescription,
    ]);

    $app->stop();
};

$app->param = $app->container->protect($param);
$app->json = $app->container->protect($json);
$app->jsonError = $app->container->protect($jsonError);
