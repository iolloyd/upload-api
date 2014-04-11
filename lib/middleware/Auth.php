<?php

class HttpBasicAuth extends \Slim\Middleware
{
    protected 
        $entityManager, 
        $realm;

    public function __construct($entityManager, $realm='Protected Area')
    {
        $this->entityManager = $entityManager;
        $this->realm = $realm;
    }

    public function call()
    {
        $request = $this->app->request();
        $response = $this->app->response();
        $authUser = $request->headers('PHP_AUTH_USER');
        $authPass = $request->headers('PHP_AUTH_PW');

        if ($this->authenticate($authUser, $authPass)) {
            $this->next->call();
        } else {
            $this->denyAccess();
        }
    }

    protected function authenticate($username, $password) 
    {
        if (isset($username) 
            && isset($password)
            && $this->isAuthorized($username, $password)
        ) {
            return true;
        }
        return false;
    }

    protected function isAuthorized($username, $password)
    {
        $user = $this->entityManager->find($username, $password);
    }

    protected function denyAccess() 
    {
        $res = $this->app->response();
        $res->status(401);
        $res->header(
            'WWW-Authenticate', 
            'Basic realm="%s"' . $this->realm        
        );
    }

}
