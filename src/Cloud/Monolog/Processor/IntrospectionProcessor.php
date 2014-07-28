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

namespace Cloud\Monolog\Processor;

use Monolog\Logger;
use Symfony\Component\Filesystem\Filesystem;

class IntrospectionProcessor
{
    protected $level;
    protected $skipClassesPartials;
    protected $fs;

    public function __construct($level = Logger::DEBUG, array $skipClassesPartials = ['Cloud\\Monolog\\', 'Monolog\\'])
    {
        $this->level = $level;
        $this->skipClassesPartials = $skipClassesPartials;
        $this->fs = new Filesystem();
    }

    /**
     * @param  array $record
     * @return array
     */
    public function __invoke(array $record)
    {
        // return if the level is not high enough
        if ($record['level'] < $this->level) {
            return $record;
        }

        $trace = debug_backtrace();

        // skip first since it's always the current method
        array_shift($trace);
        // the call_user_func call is also skipped
        array_shift($trace);

        $i = 0;

        while (isset($trace[$i]['class'])) {
            foreach ($this->skipClassesPartials as $part) {
                if (strpos($trace[$i]['class'], $part) !== false) {
                    $i++;
                    continue 2;
                }
            }
            break;
        }

        $file = isset($trace[$i-1]['file']) ? $trace[$i-1]['file'] : null;
        $line = isset($trace[$i-1]['line']) ? $trace[$i-1]['line'] : null;

        $file = $this->fs->makePathRelative($file, getcwd());
        $file = rtrim($file, '/');

        $record['extra'] = $record['extra'] + [
            'file' => $file,
            'line' => $line,
        ];

        return $record;
    }
}
