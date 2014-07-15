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

namespace CloudOutbound\YouPorn;

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
        $this->tubeConfig   = array_map('str_getcsv', file('app/config/mappings/youporn.csv'));
    }

    /**
     * @param $slug
     * @return mixed|void
     */
    public function getCategoryBySlug($slug)
    {
        $firstStep = $this->getMasterLookup($slug);
        $result    = $this->getTubeLookup($firstStep);
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
                return $params[3];
            }
        }

        return null;
    }

    /**
     * @param $slug
     * @return string|null
     */
    protected function getTubeLookup($slug)
    {
        foreach ($this->tubeConfig as $params) {
            if ($slug == $params[1]) {
                return $params[0];
            }
        }

        return null;
    }
}


