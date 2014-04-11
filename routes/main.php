<?php

function loadTestData($config) {
    $testVideo = $config('dev')['test.video'];
    $testDestination = $config('dev')['test.destination'];

    $devData = [
        ['title' => 'Angie likes chickens', 'description' => 'A video about feather love', 
            'tags' => ['feathers', 'eggs', 'chickens', 'farms']],

        ['title' => 'Angie likes apples', 'description' => 'A tale of fruity desires', 
            'tags' => ['fruits', 'green', 'tempts', 'grows']],

        ['title' => 'Carol wants steak', 'description' => 'Loving story of meaty madness',
            'tags' => ['steak', 'gravy', 'yummy', 'succulent', 'protein']]];

    $devUser = [
        'email' => 'dev@cloud.com', 
        'username' => 'dev',
        'password' => 'secure',

    ];

    $user = R::dispense('user');
    $user->import($devUser);
    R::store($user);

    foreach ($devData as $info) {
        $video = R::dispense('video');
        $video->import($info, 'title,description');
        $video->path = $testVideo;
        $video->destination = $testDestination;
        R::tag($video, $info['tags']);
        R::store($video);
    }
}

function testDatabase() {
    $user = R::findAll('user');
    if (count($user)) {
        return true;
    }

    throw new Exception("Problem with database");
}

$app->get('/', function() use ($app) {
    $app->render('welcome.html');
});

$app->get('/helper/setup', function() use ($app, $config) {
    R::nuke();
    try {
        loadTestData($config);
        testDatabase();
    } catch (Exception $e) {
        echo $e->getMessage();
        exit();
    }
    echo "All systems are go. Yay!. The dev user credentials:<br/>";
    $user = R::exportAll(R::findOne('user'))[0];

    echo "user: <strong>{$user['username']}</strong> <br/>
        email: <strong>{$user['email']}</strong> <br/>
        password: <strong>{$user['password']}</strong><br/><br/>";
    echo "Click <a href='/login'>Here</a> to login<br/>"; 
    echo "You only need to enter the email currently";
});

