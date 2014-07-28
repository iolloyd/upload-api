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

class ConsoleFormatter extends LineFormatter
{
    const SIMPLE_DATE = "H:i:s";
    const SIMPLE_FORMAT = "[%datetime%] [%channel%] %start_tag%%level_name%:%end_tag% %message% %extra% %context%\n";

    /**
     * {@inheritDoc}
     */
    public function format(array $record)
    {
        if ($record['level'] >= Logger::ERROR) {
            $record['start_tag'] = '<error>';
            $record['end_tag']   = '</error>';
        } elseif ($record['level'] >= Logger::NOTICE) {
            $record['start_tag'] = '<comment>';
            $record['end_tag']   = '</comment>';
        } elseif ($record['level'] >= Logger::INFO) {
            $record['start_tag'] = '<info>';
            $record['end_tag']   = '</info>';
        } else {
            $record['start_tag'] = '';
            $record['end_tag']   = '';
        }

        $record['level_name'] = sprintf('%6s', $record['level_name']);
        $record['message']    = sprintf('%-50s', $record['message']);

        return parent::format($record);
    }

    protected function toJson($data, $ignoreErrors = false)
    {
        return preg_replace('/\s\s+/', ' ', json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
}
