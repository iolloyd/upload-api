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

namespace Cloud\Model\Traits;

use Cloud\Model\Company;

trait CompanyTrait
{
    /**
     * @ORM\JoinColumn(nullable=false)
     * @ORM\ManyToOne(targetEntity="Cloud\Model\Company")
     * @CX\Company
     */
    protected $company;

    /**
     * Set the company who created the model
     *
     * @param  Company $company
     * @return CompanyTrait
     */
    public function setCompany(Company $company)
    {
        $this->company = $company;
        return $this;
    }

    /**
     * Set the parent company
     *
     * @return Company
     */
    public function getCompany()
    {
        return $this->company;
    }

}
