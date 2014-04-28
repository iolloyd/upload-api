<?php

$app->configureMode('development', function () use ($app) {
    // slim
    $app->config([
        'debug'       => true,
        'log.enabled' => false,
    ]);

    // db
    $app->config([
        'db.dsn'      => "mysql:host=localhost;dbname=cloudxxx",
        'db.username' => 'root',
        'db.password' => 'root',
    ]);
    
    // app
    $app->config([
        'app.baseurl' => $app->request->getUrl(),
    ]);

    // amazon
    $app->config([
        's3.bucket' => 'cldsys-dev',
        's3.key'    => 'AKIAJFJWKRRF6DGEPXCA',
        's3.secret' => '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',
        's3.region' => 'us-west-2',
    ]);

    $app->config([
        'dev.video' => 'some/test/video',
        'dev.destination' => 'some/test/destination'
    ]);

    $app->config([
        'dev.users' => [
            [
                'username' => 'dev',
                'email' => 'dev@cloud.com',
                'password' => 'password'
            ],
            [
                'username' => 'nok',
                'email' => 'other@cloud.com',
                'password' => 'other' 
            ]
        ],

        'dev.videos' => [
            [
                'path' => 'path',
                'title' => 'Angie likes chickens', 
                'description' => 'A video about feather love', 
                'tags' => [
                    'feathers', 'eggs', 'chickens', 'farms'
                ]
            ],
            [
                'path' => 'path',
                'title' => 'Angie likes apples', 
                'description' => 'A tale of fruity desires', 
                'tags' => [
                    'fruits', 'green', 'tempts', 'grows'
                ]
            ],
            [
                'path' => 'path',
                'title' => 'Carol wants steak', 
                'description' => 'Loving story of meaty madness',
                'tags' => [
                    'steak', 'gravy', 'yummy', 'succulent', 'protein'
                ]
            ]
        ]
    ]);


});
