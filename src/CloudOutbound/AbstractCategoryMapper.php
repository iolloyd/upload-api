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

use Cloud\Model\Category;
use Exception;

class AbstractCategoryMapper implements CategoryMapperInterface
{
    protected $config = null;

    /**
     * @param $slug
     * @return mixed|void
     */
    public function convert(Category $category)
    {
        if (!$this->config) {
            throw new Exception("You must set a config file");
        }

        $config = array_map('str_getcsv', file($this->config));
        $slug   = $category->getSlug();

        foreach ($config as $params) {
            if ($slug == $params[0]) {
                return end($params);
            }
        }

        throw new Exception(sprintf("Could not find mapping for category slug %s", $slug));
    }

}

