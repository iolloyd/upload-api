<?php

/*
 * Set error reporting to the level to which code must comply.
 */
error_reporting( E_ALL | E_STRICT );

if (class_exists('PHPUnit_Runner_Version', true)) {
    $phpUnitVersion = PHPUnit_Runner_Version::id();
    if ('@package_version@' !== $phpUnitVersion && version_compare($phpUnitVersion, '3.7.0', '<')) {
        echo 'This version of PHPUnit (' . PHPUnit_Runner_Version::id() . ') is not supported'
           . ' in Zend Framework 2.x unit tests. Supported is version 3.7.0  or higher.'
           . ' See also: https://github.com/zendframework/zf2/blob/master/CONTRIBUTING.md#running-tests' . PHP_EOL;
        exit(1);
    }
    unset($phpUnitVersion);
}

/*
 * Determine the root, library, and tests directories of the framework
 * distribution.
 */
$zfRoot        = realpath(dirname(__DIR__));
$zfCoreLibrary = "$zfRoot/src";
$zfCoreTests   = "$zfRoot/tests";

/*
 * Prepend the Zend Framework library/ and tests/ directories to the
 * include_path. This allows the tests to run out of the box and helps prevent
 * loading other copies of the framework code and tests that would supersede
 * this copy.
 */
$path = array(
    $zfCoreLibrary,
    $zfCoreTests,
    get_include_path(),
);
set_include_path(implode(PATH_SEPARATOR, $path));

/**
 * Setup autoloading
 */
include __DIR__ . '/_autoload.php';

/*
 * Load the user-defined test configuration file, if it exists; otherwise, load
 * the default configuration.
 */
if (is_readable($zfCoreTests . DIRECTORY_SEPARATOR . 'TestConfiguration.php')) {
    require_once $zfCoreTests . DIRECTORY_SEPARATOR . 'TestConfiguration.php';
} else {
    require_once $zfCoreTests . DIRECTORY_SEPARATOR . 'TestConfiguration.php.dist';
}

if (defined('TESTS_GENERATE_REPORT') && TESTS_GENERATE_REPORT === true) {
    $codeCoverageFilter = new PHP_CodeCoverage_Filter();

    $lastArg = end($_SERVER['argv']);
    if (is_dir($zfCoreTests . '/' . $lastArg)) {
        $codeCoverageFilter->addDirectoryToWhitelist($zfCoreLibrary . '/' . $lastArg);
    } elseif (is_file($zfCoreTests . '/' . $lastArg)) {
        $codeCoverageFilter->addDirectoryToWhitelist(dirname($zfCoreLibrary . '/' . $lastArg));
    } else {
        $codeCoverageFilter->addDirectoryToWhitelist($zfCoreLibrary);
    }

    /*
     * Omit from code coverage reports the contents of the tests directory
     */
    $codeCoverageFilter->addDirectoryToBlacklist($zfCoreTests, '');
    $codeCoverageFilter->addDirectoryToBlacklist(PEAR_INSTALL_DIR, '');
    $codeCoverageFilter->addDirectoryToBlacklist(PHP_LIBDIR, '');

    unset($codeCoverageFilter);
}


/**
 * Start output buffering, if enabled
 */
if (defined('TESTS_ZEND_OB_ENABLED') && constant('TESTS_ZEND_OB_ENABLED')) {
    ob_start();
}

/*
 * Unset global variables that are no longer needed.
 */
unset($zfRoot, $zfCoreLibrary, $zfCoreTests, $path);