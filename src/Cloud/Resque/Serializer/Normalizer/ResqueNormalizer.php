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

namespace Cloud\Resque\Serializer\Normalizer;

use Cloud\Resque\Resque;
use Symfony\Component\Serializer\Normalizer\CustomNormalizer;

/**
 * Resque aware normalizer which injects the Resque management instance as the
 * first constructor parameter
 */
class ResqueNormalizer extends CustomNormalizer
{
    /**
     * @var Resque
     */
    protected $resque;

    /**
     * Constructor
     */
    public function __construct(Resque $resque)
    {
        $this->resque = $resque;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        return $object->normalize($this->serializer, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        $object = new $class($this->resque);
        $object->denormalize($this->serializer, $data, $format, $context);

        return $object;
    }
}
