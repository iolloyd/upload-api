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

namespace CloudOutbound;

use DomainException;
use UnexpectedValueException;
use Cloud\Model\Category;

abstract class AbstractCategoryMapper implements CategoryMapperInterface
{
    protected $config = null;

    /**
     * @param $slug
     * @return mixed|void
     */
    public function convert(Category $category)
    {
        if (!$this->config) {
            throw new DomainException('You must set a config file');
        }

        $config = array_map('str_getcsv', file($this->config));
        $slug   = $category->getSlug();

        foreach ($config as $params) {
            if ($slug == $params[0]) {
                return end($params);
            }
        }

        throw new UnexpectedValueException(sprintf(
            'Could not find mapping for category slug `%s`',
            $slug
        ));
    }

}

