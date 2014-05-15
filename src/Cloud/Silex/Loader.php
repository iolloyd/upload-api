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

namespace Cloud\Silex;

use Silex\ServiceProviderInterface;
use Symfony\Component\Finder\Finder;

/**
 * Dynamic loading of modules and other subcomponents for Slim
 *
 * ```php
 * <?php
 * $app->register(new \Cloud\Silex\Loader(), [
 *     'loader.path' => 'app/',
 *     'loader.extensions' => [
 *         'php',
 *     ],
 * ]);
 * $app['load']('helper');
 * $app['load']('routes');
 *
 * $app->run();
 * ```
 */
class Loader implements ServiceProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function register(\Silex\Application $app)
    {
        $app['load'] = $app->protect(function ($dir) use ($app) {
            $path = $app['loader.path'] ?: 'app/';
            $ext  = $app['loader.extensions'] ?: ['php'];

            $dir  = $path . $dir;

            if (is_dir($dir)) {
                $finder = new Finder();
                $finder
                    ->files()
                    ->in($dir);

                foreach ($ext as $x) {
                    $finder->name('*.' . $x);
                }

                foreach ($finder as $filepath) {
                    Cloud_Silex_Loader__require($filepath, $app);
                }
            }
        });
    }

    /**
     * {@inheritDoc}
     */
    public function boot(\Silex\Application $app)
    {
    }
}

/**
 * Isolate scope for included files to prevent access to $this and self
 */
function Cloud_Silex_Loader__require($filepath, $app) {
    require $filepath;
}
