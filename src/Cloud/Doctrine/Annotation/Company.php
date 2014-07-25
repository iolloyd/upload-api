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

namespace Cloud\Doctrine\Annotation;

/**
 * @Annotation
 * @Target({"PROPERTY"})
 */
class Company
{
    /**
     * @var boolean  if true, allow unfiltered listing when no user is logged
     *                 in
     */
    public $allowAnonymous = false;
}
