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

namespace Cloud\Monolog\Formatter;

use Monolog\Logger;
use Monolog\Formatter\LineFormatter as BaseLineFormatter;

class LineFormatter extends BaseLineFormatter
{
    const SIMPLE_FORMAT = "[%datetime%] %level_name% [%channel%] %message%\n  %extra%\n";

    /**
     * {@inheritDoc}
     */
    public function format(array $record)
    {
        //$record['level_name'] = sprintf('%6s', $record['level_name']);

        return parent::format($record);
    }
}
