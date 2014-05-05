<?php

namespace Cloud\Console;

use Slim\Middleware as BaseMiddleware;
use Symfony\Component\Console\Input\ArgvInput;

/**
 * Console Application Dispatch
 */
class Middleware extends BaseMiddleware
{
    /**
     * Middleware Call
     */
    public function call()
    {
        $app = $this->app;

        $input = new ArgvInput();
        $cli = new Application($app);
        $cli->run($input);

        $app->stop();
    }
}
