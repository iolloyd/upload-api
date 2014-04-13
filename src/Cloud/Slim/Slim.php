<?php

namespace Cloud\Slim;

use Slim\Slim as BaseSlim;

/**
 * Extended Slim base class
 *
 *  - Support injecting callable functions into `$app`
 */
class Slim extends BaseSlim
{
    /**
     * Invoke callable variables
     *
     * @param string $method
     * @param array  $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        $callback = $this->{$method};

        if (!is_callable($callback)) {
            throw new \Exception(sprintf(
                'Cannot call %s::%s(): $app->%s must be callable, got %s',
                get_called_class(), $method, $method,
                gettype($callback)
            ));
        }

        return call_user_func_array($callback, $args);
    }
}
