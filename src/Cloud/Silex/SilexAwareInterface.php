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
 * Interface for classes that depend on the Silex application
 */
interface SilexAwareInterface
{
    /**
     * Set the Silex application
     *
     * This method injects the primary silex application instance into
     * this class
     *
     * @param  \Silex\Silex $application
     * @return SilexAwareInterface
     */
    public function setApplication(\Cloud\Silex\Application $app);

    /**
     * Get the Silex application
     *
     * @return \Cloud\Silex\Application
     */
    public function getApplication();
}
