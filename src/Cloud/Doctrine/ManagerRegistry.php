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

namespace Cloud\Doctrine;

use Doctrine\Common\Persistence\AbstractManagerRegistry;
use Doctrine\ORM\ORMException;
use Silex\Application;

class ManagerRegistry extends AbstractManagerRegistry
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * {@inheritDoc}
     */
    public function __construct(Application $app, $name, $connectionContainer, $managerContainer, array $connections, array $managers, $defaultConnection, $defaultManager)
    {
        $this->app = $app;

        $connections = array_map(function ($d) use ($connectionContainer) {
            return $connectionContainer . ':' . $d;
        }, $connections);

        $managers = array_map(function ($d) use ($managerContainer) {
            return $managerContainer . ':' . $d;
        }, $managers);

        parent::__construct(
            $name,
            $connections,
            $managers,
            $defaultConnection,
            $defaultManager,
            'Doctrine\Common\Proxy\Proxy'
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getConnectionNames()
    {
        return array_keys($this->connections);
    }

    /**
     * {@inheritDoc}
     */
    public function getManagerNames()
    {
        return array_keys($this->managers);
    }

    /**
     * Resolves a registered namespace alias to the full namespace.
     *
     * This method looks for the alias in all registered entity managers.
     *
     * @param string $alias The alias
     *
     * @return string The full namespace
     */
    public function getAliasNamespace($alias)
    {
        foreach (array_keys($this->getManagers()) as $name) {
            try {
                return $this->getManager($name)->getConfiguration()->getEntityNamespace($alias);
            } catch (ORMException $e) {
            }
        }

        throw ORMException::unknownEntityNamespace($alias);
    }

    /**
     * {@inheritDoc}
     */
    protected function getService($id)
    {
        list($container, $name) = explode(':', $id);
        return $this->app[$container][$name];
    }

    /**
     * {@inheritDoc}
     */
    protected function resetService($id)
    {
        list($container, $name) = explode(':', $id);
        unset($this->app[$container][$name]);
    }
}
