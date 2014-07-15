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

namespace Cloud\Resque\Plugin\Status\Job;

use DateTime;
use InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizableInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizableInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Job status model
 */
class Status implements NormalizableInterface, DenormalizableInterface
{
    const STATUS_SCHEDULED = 'scheduled';

    const STATUS_QUEUED    = 'queued';
    const STATUS_WORKING   = 'working';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED    = 'failed';
    const STATUS_KILLED    = 'killed';

    //////////////////////////////////////////////////////////////////////////

    /**
     * @var string
     */
    protected $uuid;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $status;

    /**
     * @var string
     */
    protected $message;

    /**
     * @var array
     */
    protected $payload;

    /**
     * @var DateTime
     */
    protected $createdAt;

    /**
     * @var DateTime
     */
    protected $updatedAt;

    /**
     * @var DateTime
     */
    protected $startedAt;

    /**
     * @var DateTime
     */
    protected $finishedAt;

    /**
     * Constructor
     *
     * @param string $uuid  identifier for the queued job
     */
    public function __construct($uuid = null)
    {
        $this->uuid = $uuid;
    }

    public function getUuid()
    {
        return $this->uuid;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setStatus($status, $message = null)
    {
        if (!in_array($status, [
            self::STATUS_QUEUED,
            self::STATUS_WORKING,
            self::STATUS_COMPLETED,
            self::STATUS_FAILED,
            self::STATUS_KILLED,
        ])) {
            throw new InvalidArgumentException("Invalid status");
        }

        $this->status = $status;
        $this->message = $message;

        return $this;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set the original job payload
     *
     * @param  array $payload
     * @return Status
     */
    public function setPayload(array $payload)
    {
        $this->payload = $payload;
        return $this;
    }

    /**
     * Get the original job payload
     *
     * @return array
     */
    public function getPayload()
    {
        return $this->payload;
    }

    public function setProgress($stepsCompleted, $totalSteps, $message = null)
    {
    }

    public function getProgress()
    {
    }

    public function getStepsCompleted()
    {
        return 0;
    }

    public function getTotalSteps()
    {
        return 0;
    }

    public function setResult($result)
    {
    }

    public function getResult()
    {
    }

    public function getCreatedAt()
    {
        if (!$this->createdAt) {
            $this->createdAt = new DateTime();
        }

        return $this->createdAt;
    }

    //////////////////////////////////////////////////////////////////////////

    public function hasPerformed()
    {
        return $this->isCompleted()
            || $this->isFailed()
            || $this->isKilled();
    }

    public function hasError()
    {
        return $this->isFailed()
            || $this->isKilled();
    }

    //////////////////////////////////////////////////////////////////////////

    public function isQueued()
    {
        return $this->status == static::STATUS_QUEUED;
    }

    public function isWorking()
    {
        return $this->status == static::STATUS_WORKING;
    }

    public function isCompleted()
    {
        return $this->status == static::STATUS_COMPLETED;
    }

    public function isFailed()
    {
        return $this->status == static::STATUS_FAILED;
    }

    public function isKilled()
    {
        return $this->status == static::STATUS_KILLED;
    }

    //////////////////////////////////////////////////////////////////////////

    /**
     * Normalizes the object into an array of scalars|arrays
     */
    public function normalize(NormalizerInterface $normalizer, $format = null, array $context = [])
    {
        return [
            'uuid'    => $this->getUuid(),
            'name'    => $this->getName(),

            'status'  => $this->getStatus(),
            'num'     => $this->getStepsCompleted(),
            'total'   => $this->getTotalSteps(),
            'message' => $this->getMessage(),
            'payload' => $this->getPayload(),

            'time'    => $this->getCreatedAt()->getTimestamp(),
        ];
    }

    /**
     * Denormalizes the object back from an array of scalars|arrays
     */
    public function denormalize(DenormalizerInterface $denormalizer, $data, $format = null, array $context = [])
    {
        $this->uuid           = $data['uuid'];
        $this->name           = $data['name'];
        $this->status         = $data['status'];
        $this->stepsCompleted = $data['num'];
        $this->totalSteps     = $data['total'];
        $this->message        = $data['message'];
        $this->payload        = isset($data['payload']) ? $data['payload'] : null;
        $this->createdAt      = DateTime::createFromFormat('U', $data['time']);
    }

    /**
     * Generate a string representation of this object
     *
     * @return string
     */
    public function __toString()
    {
        return 'status:' . $this->getUuid();
    }
}


