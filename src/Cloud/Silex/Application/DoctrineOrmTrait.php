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

namespace Cloud\Silex\Application;

trait DoctrineOrmTrait
{
    /**
     * Execute a given callback with the Doctrine EntityManager in an explicit
     * transaction
     *
     * The callback receives the EntityManager `$em` as an optional first
     * parameter.
     *
     *    $model = $app->transactional(function ($em) use ($request, $foo) {
     *        $model = new Model();
     *        $model->setField($request->get('bazbar'));
     *
     *        // this will be auto-flushed:
     *        $em->persist($model);
     *
     *        // this will be auto-flushed:
     *        $foo->setSomethingElse(123);
     *
     *        // if everything executes successfully, the model is returned to
     *        // the parent scope
     *        return $model;
     *    });
     *
     * If an exception occurs during execution of the function or flushing or
     * transaction commit, the transaction is rolled back, the EntityManager
     * closed and the exception re-thrown.
     *
     * @param callable $callback
     *
     * @return mixed|true
     */
    public function transactional(callable $callback)
    {
        return $this['em']->transactional($callback);
    }
}
