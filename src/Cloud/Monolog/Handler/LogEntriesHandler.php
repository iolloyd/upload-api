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

use RuntimeException;
use Monolog\Handler\LogEntriesHandler as BaseLogEntriesHandler;

/**
 * LogEntries handler with support for multi-line messages
 */
class LogEntriesHandler extends BaseLogEntriesHandler
{
    /**
     * {@inheritDoc}
     */
    public function handle(array $record)
    {
        // prevent application crashes if we can't connect to logentries

        try {
            return parent::handle($record);
        } catch (RuntimeException $e) {
            trigger_error(sprintf('%s(): %s', __METHOD__, $e->getMessage()), E_USER_WARNING);
            return false;
        }
    }

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
