<?php
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;


$app->configureMode('development', function () use ($app) {
    // slim
    $app->config([
        'debug'       => true,
        'log.enabled' => false,
    ]);

    // db
    $app->config([
        'db.dsn'      => "mysql:host=localhost;dbname=test_cloudxxx",
        'db.username' => 'root',
        'db.password' => 'root',
    ]);
    
    $app->config([
        'doctrine.driver' => 'pdo_mysql',
        'doctrine.dbname' => 'test_cloudxxx',
        'doctrine.user' => 'root',
        'doctrine.password' => 'root',
        'doctrine.host' => 'localhost'
    ]); 
    
    // app
    $app->config([
        'app.baseurl' => $app->request->getUrl(),
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
                'filename' => 'filename',
                'title' => 'Angie likes chickens', 
                'description' => 'A video about feather love', 
                'tags' => [
                    'feathers', 'eggs', 'chickens', 'farms'
                ]
            ],
            [
                'filename' => 'filename',
                'title' => 'Angie likes apples', 
                'description' => 'A tale of fruity desires', 
                'tags' => [
                    'fruits', 'green', 'tempts', 'grows'
                ]
            ],
            [
                'filename' => 'filename',
                'title' => 'Carol wants steak', 
                'description' => 'Loving story of meaty madness',
                'tags' => [
                    'steak', 'gravy', 'yummy', 'succulent', 'protein'
                ]
            ]
        ]
    ]);


});
