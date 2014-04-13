<?php

namespace Cloud\Slim\Loader;

use FilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Locate PHP files in a given directory
 */
class FileIterator extends FilterIterator
{
    /**
     * @var array
     */
    protected $extensions;

    /**
     * Constructor
     *
     * @param string $path
     *
     */
    public function __construct($path, array $extensions = [])
    {
        $this->extensions = $extensions;

        $iterator = new RecursiveDirectoryIterator(
            $path,
            RecursiveDirectoryIterator::FOLLOW_SYMLINKS | RecursiveDirectoryIterator::SKIP_DOTS
        );

        $iterator = new RecursiveIteratorIterator($iterator);

        parent::__construct($iterator);
    }

    /**
     * Filter for files containing only PHP
     *
     * @return bool
     */
    public function accept()
    {
        $file = $this->getInnerIterator()->current();

        // if we have a directory, it's not a file, so return false
        if (!$file->isFile()) {
            return false;
        }

        // if it's a hidden file, return false
        if (!$file->isFile()) {
            return false;
        }

        // if not a .php file, skip
        if (!in_array($file->getExtension(), $this->extensions)) {
            return false;
        }

        // if hidden (starts with dot), skip
        if ($file->getFilename()[0] == '.') {
            return false;
        }

        return true;
    }
}

