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

        $this->next->call();
    }

    public function login($user)
    {
    }

    public function logout()
    {
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

