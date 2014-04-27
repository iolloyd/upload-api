<?php

function testDatabase() {
    $user = R::findAll('user');
    if (count($user)) {
        return true;
    }

    throw new Exception("Problem with database");
}

$app->get('/', function() use ($app) {
    $app->json([
        'docs' => 'http://docs.cloudxxx.apiary.io/'
    ]);
});

$app->get('/test/setup', function() use ($app) {
    try {
        $testData = $app->config('test.data');
        testDatabase();
    } catch (Exception $e) {
        echo $e->getMessage();
        exit();
    }
    $user   = R::exportAll(R::findOne('user'))[0];
    $videos = R::exportAll(R::findAll('video'));
    $tags   = R::findAll('tag');

    $app->json(
        [
            'msg' => [
                "All systems are go. Yay!. " ,
                "The dev user credentials are ",  
                "user: {$user['username']} " ,
                "email: {$user['email']} " ,
            ],
            'stats' => [
                'videos' => [
                    'count' => count($videos),
                    'data' => $videos,
                ],
                'tags' => [
                    'count' => count($tags),
                    'data' => $tags
                ]
            ]
        ]
    );
});

