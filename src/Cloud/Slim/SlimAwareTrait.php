<?php

namespace Cloud\Slim;

/**
 * Trait implementing SlimAwareInterface
 */
trait SlimAwareTrait
{
    /**
     * @var Slim
     */
    protected $app;

    /**
     * @param  \Slim\Slim $application
     * @return SlimAwareTrait
     */
    public function setSlim(\Slim\Slim $app)
    {
        $this->app = $app;
        return $this;
    }

    /**
     * @return \Slim\Slim
     */
    public function getSlim()
    {
        return $this->app;
    }
}

