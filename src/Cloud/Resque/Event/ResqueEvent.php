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

namespace Cloud\Resque\Event;

use Cloud\Resque\Resque;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * A resque-specific event with a reference to the related `Resque` instance
 */
class ResqueEvent extends GenericEvent
{
    /**
     * Constructor
     *
     * @param Resque $resque
     * @param array  $arguments
     */
    public function __construct(Resque $resque, array $arguments = [])
    {
        parent::__construct($resque, $arguments);
    }

    /**
     * Returns the related `Resque` instance
     *
     * @return Resque
     */
    public function getResque()
    {
        return $this->getSubject();
    }
}
