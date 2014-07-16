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

interface CategoryMapperInterface
{
    /**
     * @param Category $category
     * @return mixed
     */
    public function convert(Category $category);
}



