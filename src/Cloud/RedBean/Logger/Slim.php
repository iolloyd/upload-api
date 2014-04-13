<?php

namespace Cloud\RedBean\Logger;

use RedBeanPHP\Logger;
use Slim\Slim as SlimApp;

/**
 * Log RedBean messages to Slim
 */
class Slim implements Logger
{
    /**
     * @var SlimApp
     */
    protected $app;

    /**
     * Constructor
     *
     * @param SlimApp $app
     *
     */
    public function __construct(SlimApp $app)
    {
        $this->app = $app;
    }

    /**
     * Log all parameters
     */
    public function log()
    {
        $msg = 'RedBean: ';

        foreach (func_get_args() as $argument) {
            $msg .= print_r($argument, true) . "\n";
        }

        $msg = str_replace("\n", "\n       : ", $msg);
        $msg .= "\n";

        $this->app->log->debug($msg);
    }
}


