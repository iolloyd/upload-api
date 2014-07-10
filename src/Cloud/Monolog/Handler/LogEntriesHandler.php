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

namespace Cloud\Monolog\Handler;

use Monolog\Handler\LogEntriesHandler as BaseLogEntriesHandler;

/**
 * LogEntries handler with support for multi-line messages
 */
class LogEntriesHandler extends BaseLogEntriesHandler
{
    /**
     * {@inheritDoc}
     */
    protected function generateDataStream($record)
    {
        $lines = explode("\n", $record['formatted']);

        foreach ($lines as $i => $line) {
            $lines[$i] = $this->logToken . ' ' . $line;
        }

        return implode("\n", $lines);
    }
}
