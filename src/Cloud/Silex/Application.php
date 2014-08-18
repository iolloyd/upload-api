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

use Cloud\Silex\Application\DoctrineOrmTrait;
use Cloud\Silex\Application\SecurityTrait;
use Cloud\Silex\Application\SerializerTrait;
use Silex\Application as BaseApplication;

class Application extends BaseApplication
{
    use SecurityTrait;
    use SerializerTrait;
    use DoctrineOrmTrait;
}
