<?php

namespace Cloud\Monolog\Formatter;

use Exception;
use Monolog\Formatter\NormalizerFormatter;

class LineFormatter extends NormalizerFormatter
{
    const SIMPLE_FORMAT = "[%datetime%] %channel%.%levelName%: %message% %context% %extra%\n";

    protected $format;
    protected $allowInlineLineBreaks;

    /**
     *
     * @param string $format      The message format
     * @param string $dateFormat  The timestamp format
     * @param bool   $lineBreaks  Whether to allow inline line breaks in log entries
     */
    public function __construct($format = null, $dateFormat = null, $lineBreaks = false)
    {
        $this->format = $format ?: static::SIMPLE_FORMAT;
        $this->allowInlineLineBreaks = $allowInlineLineBreaks;
        parent::__construct($dateFormat);
    }

    /**
     * {@inheritdoc}
     */
    public function format(array $record)
    {
        $vars = parent::format($record);

        $output = $this->format;
        foreach ($vars['extra'] as $var => $val) {
            if (false !== strpos($output, '%extra.'.$var.'%')) {
                $output = str_replace('%extra.'.$var.'%', $this->replaceNewlines($this->convertToString($val)), $output);
                unset($vars['extra'][$var]);
            }
        }
        foreach ($vars as $var => $val) {
            if (false !== strpos($output, '%'.$var.'%')) {
                $output = str_replace('%'.$var.'%', $this->replaceNewlines($this->convertToString($val)), $output);
            }
        }

        return $output;
    }

    public function formatBatch(array $records)
    {
        $message = '';
        foreach ($records as $record) {
            $message .= $this->format($record);
        }

        return $message;
    }

    protected function normalizeException(Exception $e)
    {
        $previousText = '';
        if ($previous = $e->getPrevious()) {
            do {
                $previousText .= ', '.get_class($previous).': '.$previous->getMessage().' at '.$previous->getFile().':'.$previous->getLine();
            } while ($previous = $previous->getPrevious());
        }

        return '[object] ('.get_class($e).': '.$e->getMessage().' at '.$e->getFile().':'.$e->getLine().$previousText.')';
    }

    protected function convertToString($data)
    {
        if (null === $data || is_bool($data)) {
            return var_export($data, true);
        }

        if (is_scalar($data)) {
            return (string) $data;
        }

        if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
            return $this->toJson($data, true);
        }

        return str_replace('\\/', '/', @json_encode($data));
    }

    protected function replaceNewlines($str)
    {
        if ($this->allowInlineLineBreaks) {
            return $str;
        }

        return preg_replace('{[\r\n]+}', ' ', $str);
    }
}
