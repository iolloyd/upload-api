<?php

namespace Cloud\Slim;

/**
 * Interface for classes that depend on the Slim application
 */
interface SlimAwareInterface
{
    /**
     * Set the Slim application
     *
     * This method injects the primary Slim application instance into
     * this class
     *
     * @param  \Slim\Slim $application
     * @return SlimAwareInterface
     */
    public function setSlim(\Slim\Slim $app);

    /**
     * Get the Slim application
     *
     * @return \Slim\Slim
     */
    public function getSlim();
}
