<?php

$app->get('/tags/', function() use ($app) {
    $tags = R::findAll('tag');
    $app->render('forms/tag.html', [
        'tags' => $tags
    ]);
});

$app->get('/label/remove/:id', function($id) use ($app) {
    $todelete = R::load('tag', $id);
    R::trash($todelete);
    $app->redirect('/tags/');
});

$app->post('/tags/', function() use ($app) {
    $tag = R::dispense('tag');
    $tag->label = $_POST['label'];
    R::store($tag);
    $app->redirect('/tags');
});

