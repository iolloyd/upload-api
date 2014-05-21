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

namespace Cloud\Silex\Converter;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DoctrineOrmConverter
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var string
     */
    protected $entityName;

    /**
     * Constructor
     *
     * @param  EntityManager $em
     * @param  string        $entityName
     */
    public function __construct(EntityManager $em, $entityName)
    {
        $this->em = $em;
        $this->entityName = $entityName;
    }

    /**
     * Convert parameter to ORM entity
     *
     * @param  int $id  entity identifier
     * @return object
     */
    public function convert($id)
    {
        $entity = $this->em->find($this->entityName, (int) $id);

        if (!$entity) {
            throw new NotFoundHttpException(
                sprintf('Entity #%d does not exist', $id)
            );
        }

        return $entity;
    }

    /**
     * Convert parameter to ORM entity list
     *
     * @return array object
     */
    public function convertAll()
    {
        $entities = $this->em->getRepository($this->entityName)->findAll();
        if (!$entities) {
            throw new NotFoundHttpException(
                sprintf('Entities #%d do not exist')
            );
        }

        return $entities;
    }
}
