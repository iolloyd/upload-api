<?php

namespace Cloud\Monolog\Formatter;

use Exception;
use Monolog\Formatter\NormalizerFormatter;

class LineFormatter extends NormalizerFormatter
{
    const SIMPLE_FORMAT = "[%datetime%] [%channel%] %level_name%: %message% \n%context% %extra%\n";

    protected $format;
    protected $allowInlineLineBreaks;

    /**
     *
     * @param string $format      The message format
     * @param string $dateFormat  The timestamp format
     * @param bool   $lineBreaks  Whether to allow inline line breaks in log entries
     */
    public function __construct($format = null, $dateFormat = null, $lineBreaks = true)
    {
        $this->format = $format ?: static::SIMPLE_FORMAT;
        $this->allowInlineLineBreaks = $lineBreaks;
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
                $cleanString = $this->getCleanString($val);
                $output = str_replace('%extra.'.$var.'%', $cleanString, $output);
                unset($vars['extra'][$var]);
            }
        }

        foreach ($vars as $var => $val) {
            if (false !== strpos($output, '%'.$var.'%')) {
                $cleanString = $this->getCleanString($val);
                $output = str_replace('%'.$var.'%', $cleanString, $output);
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

    protected function getCleanString($string)
    {
        $string = $this->convertToString($string);
        $cleanString = $this->replaceNewLines($string);
        return $cleanString;
    }

    protected function normalizeException(Exception $e)
    {
        $previousText = '';
        if ($previous = $e->getPrevious()) {
            do {
                $previousText .= ', '.get_class($previous) . ': '
                . $this->messageFileLine($previous);

            } while ($previous = $previous->getPrevious());
        }

        return '[object] ('.get_class($e).': '
            . $this->messageFileLine($e)
            . $previousText    . ')';
    }

    protected function messageFileLine(Exception $e)
    {
        return $e->getMessage() . ' at '
            . $e->getFile()  . ':'
            . $e->getLine();
    }

    protected function convertToString($data)
    {
        if (null === $data || is_scalar($data)) {
            return (string) $data;
        }

        if (is_object($data) && !get_object_vars($data)
            || is_array($data) && !$data
        ) {
            return '';
        }

        $data = $this->normalize($data);
        if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
            return $this->toJson($data);
        }

        return str_replace('\\/', '/', json_encode($data));
        return $data;
    }

    protected function replaceNewlines($str)
    {
        if ($this->allowInlineLineBreaks) {
            return $str;
        }

        return preg_replace('{[\r\n]+}', ' ', $str);
    }
}
