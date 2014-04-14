<?php

namespace Cloud\Slim\Middleware;

use Slim\Middleware;

/**
 * Session and Authentication
 */
class Session extends Middleware
{
    /**
     * Call Middleware
     */
    public function call()
    {
        $app = $this->app;

        $app->session = function () {
            return $this;
        };

        $app->user = function () {
            return isset($_SESSION['user']) ? $_SESSION['user'] : null;
        };

        $app->account = function () {
            return isset($_SESSION['account']) ? $_SESSION['account'] : null;
        };

        $this->next->call();
    }

    public function login(array $data)
    {
        $this->logout();

        if ($data['password'] != '123') {
            return false;
        }

        $user = $data;
        $_SESSION['user'] = $user;

        return $user;
    }

    public function logout()
    {
        session_regenerate_id(true);
        session_destroy();
        session_start();
        return true;
    }

    public function isLoggedIn()
    {
        return (bool) $this->app->user;
    }

    public function authorize($scope = null)
    {
        return function ($route) {

        };
    }
}

