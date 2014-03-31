<?php

function notAuthenticated($email, $password) {
    return $email != 'me' || $password != 'me';
}

$app->get('/login', function() use ($app) {
    $app->render('login.html', array('error' => $app->flash));
});

$app->post("/login", function () use ($app) {
    $email = $app->request()->post('email');
    $password = $app->request()->post('password');
    $errors = array();

    if (notAuthenticated($email, $password)) {
        $app->flash('error', 'Email or password not good');
        $app->redirect('/login');
    }

    $_SESSION['user'] = $email;

    if (isset($_SESSION['urlRedirect'])) {
        $tmp = $_SESSION['urlRedirect'];
        unset($_SESSION['urlRedirect']);
        $app->redirect($tmp);
    }
    $app->redirect('/');
});

$app->get('/logout', function() use ($app) {
    $_SESSION['user'] = false;
    $app->redirect('/login');
});
