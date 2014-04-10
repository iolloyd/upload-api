<?php

function authenticated($email, $password) {
    $user = R::getRow(
        'SELECT * FROM user WHERE email=:email',
        ['email' => $email]
    );

    return $user && $user['email'] == $email;
}

$app->get('/login', function() use ($app) {
    $app->render('login.html', ['error' => $app->flash]);
});

$app->post("/login", function() use ($app) {
    $email = $app->request()->post('email');
    $password = $app->request()->post('password');

    if (authenticated($email, $password)) {
        $_SESSION['user'] = $email;

        if (isset($_SESSION['urlRedirect'])) {
            $nextUrl = $_SESSION['urlRedirect'];
            unset($_SESSION['urlRedirect']);
            $app->redirect($nextUrl);
        }
        $app->redirect('/');
    } else {
        $app->redirect('/login');
    }

});

$app->get('/logout', function() use ($app) {
    $_SESSION['user'] = false;
    $app->redirect('/login');
});
