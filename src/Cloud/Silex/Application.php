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

use Silex\Application as BaseApplication;
use Silex\Application\SecurityTrait;
use Silex\Application\MonologTrait;

class Application extends BaseApplication
{
    use SecurityTrait;
    use MonologTrait;
}
