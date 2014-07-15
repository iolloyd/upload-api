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

/**
 * Interface TubeCategoryStrategyInteface
 *
 * @package CloudOutbound
 */
interface TubeCategoryStrategyInterface
{
    /**
     * @param $slug
     * @return mixed
     */
    public function getCategoryBySlug($slug);
}



