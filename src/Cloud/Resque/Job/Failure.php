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

namespace Cloud\Resque\Job;

use DateTime;
use JsonSerializable;
use Cloud\Resque\Resque;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizableInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizableInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Failed job logging model
 */
class Failure implements NormalizableInterface, DenormalizableInterface, JsonSerializable
{
    /**
     * @var Resque
     */
    protected $resque;

    /**
     * @var DateTime
     */
    protected $failedAt;

    /**
     * @var array
     */
    protected $payload;

    /**
     * @var string
     */
    protected $error;

    /**
     * @var string
     */
    protected $exception;

    /**
     * @var array
     */
    protected $backtrace;

    /**
     * @var string
     */
    protected $worker;

    /**
     * @var string
     */
    protected $queue;

    /**
     * Constructor
     *
     * @param Resque $resque   Resque management instance
     */
    public function __construct(Resque $resque)
    {
        $this->resque = $resque;
    }

    /**
     * Set the failure datetime
     *
     * @param  DateTime $failedAt
     * @return Failure
     */
    public function setFailedAt(DateTime $failedAt)
    {
        $this->failedAt = $failedAt;
        return $this;
    }

    /**
     * Get the failure datetime
     *
     * @return DateTime
     */
    public function getFailedAt()
    {
        if (!$this->failedAt) {
            $this->failedAt = new DateTime();
        }

        return $this->failedAt;
    }

    /**
     * Set the original job payload (including class and args)
     *
     * @param  array $payload
     * @return Failure
     */
    public function setPayload(array $payload)
    {
        $this->payload = $payload;
        return $this;
    }

    /**
     * Get the original job payload (including class and args)
     *
     * @return array
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * Set the error message
     *
     * @param  string $error
     * @return Failure
     */
    public function setError($error)
    {
        $this->error = (string) $error;
        return $this;
    }

    /**
     * Get the error message
     *
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Set the exception class name
     *
     * @param  string|Exception $exception
     * @return Failure
     */
    public function setException($exception)
    {
        if (is_object($exception)) {
            $exception = get_class($exception);
        }

        $this->exception = (string) $exception;

        return $this;
    }

    /**
     * Get the exception class name
     *
     * @return string
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * Set the exception backtrace
     *
     * @param  string $backtrace
     * @return Failure
     */
    public function setBacktrace($backtrace)
    {
        $this->backtrace = $backtrace;
        return $this;
    }

    /**
     * Get the exception backtrace
     *
     * @return string
     */
    public function getBacktrace()
    {
        return $this->backtrace;
    }

    /**
     * Set the worker name that performed the failed job
     *
     * @param  string $worker
     * @return Failure
     */
    public function setWorker($worker)
    {
        $this->worker = (string) $worker;
        return $this;
    }

    /**
     * Get the worker name that performed the failed job
     *
     * @return string
     */
    public function getWorker()
    {
        return $this->worker;
    }

    /**
     * Set the name of the queue the failed job was on
     *
     * @param  string $queue
     * @return Failure
     */
    public function setQueue($queue)
    {
        $this->queue = (string) $queue;
        return $this;
    }

    /**
     * Get the name of the queue the failed job was on
     *
     * @return string
     */
    public function getQueue()
    {
        return $this->queue;
    }

    //////////////////////////////////////////////////////////////////////////

    /**
     * Normalizes the object into an array of scalars|arrays
     */
    public function normalize(NormalizerInterface $normalizer, $format = null, array $context = [])
    {
        return [
            'failed_at' => $this->getFailedAt()->format('Y/m/d H:i:s tz'),
            'payload'   => $this->getPayload(),
            'error'     => $this->getError(),
            'exception' => $this->getException(),
            'backtrace' => explode("\n", $this->getBacktrace()),
            'worker'    => $this->getWorker(),
            'queue'     => $this->getQueue(),
        ];
    }

    /**
     * Denormalizes the object back from an array of scalars|arrays
     */
    public function denormalize(DenormalizerInterface $denormalizer, $data, $format = null, array $context = [])
    {
        $this->failedAt  = DateTime::createFromFormat('Y/m/d H:i:s tz', $data['failed_at']);
        $this->payload   = $data['payload'];
        $this->error     = $data['error'];
        $this->exception = $data['exception'];
        $this->backtrace = implode("\n", $data['backtrace']);
        $this->worker    = $data['worker'];
        $this->queue     = $data['queue'];
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->resque->normalize($this);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $item = $this->resque->normalize($this);

        return sprintf(
            '(%s: %s)',
            get_called_class(),
            implode(', ', array_map(function ($key, $value) {
                return $key . '=' . json_encode($value);
            }, array_keys($item), $item))
        );
    }
}
