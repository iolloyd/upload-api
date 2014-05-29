<?php
/**
 *
 */

namespace Cloud\Monolog\Formatter;

use Exception;

class ExceptionLineFormatter extends LineFormatter
{
    protected function normalizeException(Exception $e)
    {
        return 'Message: ' . $e->getMessage() .
               'Stack Trace: '. $e->getTraceAsString();
    }
}