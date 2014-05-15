<?php
/**
 * cloudxxx-api (http://www.cloud.xxx)
 *
 * Copyright (C) 2014 Really Useful Limited.
 * Proprietary code. Usage restrictions apply.
 *
 * @copyright  Copyright (C) 2014 Really Useful Limited
 * @license    Proprietary
 */

namespace Cloud\Silex;

/**
 * Trait implementing ApplicationAwareInterface
 */
trait SilexAwareTrait
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

