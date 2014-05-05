<?php

namespace Cloud\Slim\Loader;

use Slim\Slim;
use Slim\Middleware;

/**
 * Dynamic loading of modules and other subcomponents for Slim
 *
 * ```php
 * <?php
 *
 * $loader = new \Cloud\Slim\Loader\Loader();
 * $loader->load('routes')
 *        ->load('controllers');
 *
 * $app->add($loader);
 *
 * $app->run();
 * ```
 */
class Loader extends Middleware
{
    /**
     * @var array
     */
    protected $options = [
        'path' => 'app/',
        'extensions' => ['php'],
    ];

    /**
     * @var array
     */
    protected $files = [];

    /**
     * Constructor
     *
     * @param array $load     module folders to load automatically
     * @param array $options
     */
    public function __constructor(array $options = [])
    {
        $this->options = array_merge($this->options, $options);
    }

    /**
     * Load a folder with subcomponents
     *
     * @param string $folder  name of the folder
     * @return Loader  chainable class
     */
    public function load($folder)
    {
        $path = $this->options['path'] . $folder;

        if (is_dir($path)) {
            $iterator = new FileIterator($path, $this->options['extensions']);
            $this->files[] = array_keys(iterator_to_array($iterator));
        }

        return $this;
    }

    /**
     * Apply to `$app`
     *
     * @param Slim $app
     * @return Loader  chainable class
     */
    public function into(Slim $app)
    {
        foreach ($this->files as $group) {
            foreach ($group as $filepath) {
                $app->log->debug(sprintf('%s: loading `%s`', get_called_class(), $filepath));
                Cloud_Slim_Loader__require($filepath, $app);
            }
        }

        return $this;
    }

    /**
     * Call as Middleware
     */
    public function call()
    {
        $this->into($this->app);
        $this->next->call();
    }
}

/**
 * Isolate scope for included files to prevent access to $this and self
 */
function Cloud_Slim_Loader__require($filepath, $app) {
    require $filepath;
}
