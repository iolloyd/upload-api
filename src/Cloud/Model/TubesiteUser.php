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


namespace Cloud\Model;

use DateTime;
use JsonSerializable;
use Doctrine\Common\Collections\ArrayCollection;

use Doctrine\ORM\Mapping as ORM;
use Cloud\Doctrine\Annotation as CX;
use JMS\Serializer\Annotation as JMS;

/**
 * @ORM\Entity
 */
class TubesiteUser extends AbstractModel implements JsonSerializable
{
    use Traits\IdTrait;
    use Traits\CreatedAtTrait;
    use Traits\UpdatedAtTrait;
    use Traits\CompanyTrait;

    /**
     * @ORM\JoinColumn(nullable=false)
     * @ORM\ManyToOne(targetEntity="Tubesite")
     */
    protected $tubesite;

    /**
     * @ORM\Column(type="string")
     */
    protected $username;

    /**
     * TODO: encrypt, isolate, etc
     * @ORM\Column(type="string", length=255)
     */
    protected $password;

    /**
     * TODO: encrypt, isolate, etc
     * #Column(type="json_array")
     */
    protected $credentials;

    /**
     * @ORM\Column(type="string")
     */
    protected $externalId;

    /**
     * @ORM\Column(type="json_array")
     */
    protected $params = [];

    /**
     * Constructor
     */
    public function __construct(Tubesite $tubesite = null, Company $company = null)
    {
        if ($tubesite) {
            $this->setTubesite($tubesite);
        }

        if ($company) {
            $this->setCompany($company);
        }
    }

    /**
     * Set the parent tube site
     *
     * @param  Tubesite $tubesite
     * @return TubesiteUser
     */
    public function setTubesite(Tubesite $tubesite)
    {
        $this->tubesite = $tubesite;
        return $this;
    }

    /**
     * Get the parent tube site
     *
     * @return Tubesite
     */
    public function getTubesite()
    {
        return $this->tubesite;
    }

    /**
     * Set the company the user belongs to
     *
     * @param  Company $company
     * @return User
     */
    public function setCompany(Company $company)
    {
        $this->company = $company;
        return $this;
    }

    /**
     * Get the company the user belongs to
     *
     * @return Company
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * Set the remote username
     *
     * @param  string $username
     * @return TubesiteUser
     */
    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
    }

    /**
     * Get the remote username
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set the remote password
     *
     * @param  string $password
     * @return TubesiteUser
     */
    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }

    /**
     * Get the remote password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set the external ID
     *
     * @param  string $externalId
     * @return TubesiteUser
     */
    public function setExternalId($externalId)
    {
        $this->externalId = $externalId;
        return $this;
    }

    /**
     * Get the external ID
     *
     * @return string
     */
    public function getExternalId()
    {
        return $this->externalId;
    }

    /**
     * Set the extra account parameters
     *
     * @param  array $params
     * @return TubesiteUser
     */
    public function setParams(array $params)
    {
        $this->params = $params;
        return $this;
    }

    /**
     * Get the extra account parameters
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Add extra account parameters
     *
     * @param  array $params
     * @return TubesiteUser
     */
    public function addParams(array $params)
    {
        $this->params = array_replace($this->params, $params);
        return $this;
    }

    /**
     * Remove extra account parameters
     *
     * @param  array $keys
     * @return TubesiteUser
     */
    public function removeParams(array $keys)
    {
        $this->params = array_diff_key($this->params, array_flip($keys));
        return $this;
    }

    /**
     * Set an extra account parameter
     *
     * @param  string $key
     * @param  mixed  $value
     * @return TubesiteUser
     */
    public function setParam($key, $value)
    {
        $this->params[$key] = $value;
        return $this;
    }

    /**
     * Check if an extra account parameter exists
     *
     * @param  string $key
     * @return bool
     */
    public function hasParam($key)
    {
        return isset($this->params[$key]);
    }

    /**
     * Get an extra account parameter
     *
     * @param  string $key
     * @param  mixed  $default
     * @return mixed
     */
    public function getParam($key, $default = null)
    {
        if (!isset($this->params[$key])) {
            return $default;
        }

        return $this->params[$key];
    }

    /**
     * Remove an extra account parameter
     *
     * @param  string $key
     * @return TubesiteUser
     */
    public function removeParam($key)
    {
        unset($this->params[$key]);
        return $this;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'username' => $this->getUsername(),
        ];
    }
}

