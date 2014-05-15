<?php

namespace Cloud\Silex;

/**
 * Trait implementing ApplicationAwareInterface
 */
trait ApplicationAwareTrait
{
    /**
     * @var Slim
     */
    protected $app;

    /**
     * @param  \Cloud\Silex\Application $application
     * @return ApplicationAwareTrait
     */
    public function setApplication(\Cloud\Silex\Application $app)
    {
        $this->app = $app;
        return $this;
    }

    /**
     * @return \Cloud\Silex\Application
     */
    public function getApplication()
    {
        return $this->app;
    }
}

