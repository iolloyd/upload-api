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

namespace CloudOutbound\XVideos;

use CloudOutbound\TubeCategoryStrategyInterface;

class TubeCategoryStrategy implements TubeCategoryStrategyInterface
{
    protected $masterConfig;
    protected $tubeConfig;

    /**
     * @param      $masterConfig
     * @param null $tubeConfig
     */
    public function __construct()
    {
        $this->initialiseConfiguration();

    }

    protected function initialiseConfiguration()
    {
        $this->masterConfig = array_map('str_getcsv', file('app/config/mappings/master.csv'));
        $this->tubeConfig   = array_map('str_getcsv', file('app/config/mappings/xvideos.csv'));
    }

    /**
     * @param $slug
     * @return mixed|void
     */
    public function getCategoryBySlug($slug)
    {
        $result = $this->getMasterLookup($slug);
        return $result;
    }

    /**
     * @param $slug
     * @return string|null
     */
    protected function getMasterLookup($slug)
    {
        foreach ($this->masterConfig as $params) {
            if ($slug == $params[1]) {
                return $params[5];
            }
        }

        return null;
    }

}


