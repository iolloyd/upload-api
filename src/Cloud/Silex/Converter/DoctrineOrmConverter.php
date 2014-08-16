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
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
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
        if (!is_numeric($id)) {
            throw new BadRequestHttpException('Identifier must be numeric');
        }

        $id = (int) $id;

        $entity = $this->em->find($this->entityName, $id);

        if (!$entity) {
            throw new NotFoundHttpException(sprintf(
                'Could not find `%s` with id `%d`',
                $this->entityName,
                $id
            ));
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
        return $this->em->getRepository($this->entityName)->findAll();
    }
}
