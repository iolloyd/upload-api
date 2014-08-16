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

namespace Cloud\Serializer\Construction;

use JMS\Serializer\Construction\ObjectConstructorInterface;

abstract class AbstractConstructorDecorator implements ObjectConstructorInterface
{
    /**
     * @var ObjectConstructorInterface
     */
    protected $delegateConstructor;

    /**
     * Constructor
     *
     * @param ObjectConstructorInterface $delegateConstructor  delegate constructor to use for
     *                                                           formats other than `merge`
     */
    public function __construct(ObjectConstructorInterface $delegateConstructor)
    {
        $this->delegateConstructor = $delegateConstructor;
    }
}

