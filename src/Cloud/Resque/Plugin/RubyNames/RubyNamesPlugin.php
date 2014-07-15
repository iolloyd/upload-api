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

namespace Cloud\Resque\Plugin\RubyNames;

use Cloud\Resque\Resque;
use Cloud\Resque\Event\JobEvents;
use Cloud\Resque\Event\ResqueEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Use `Ruby::Compatible::Names` instead of the default `PHP\Slashed\Names`
 *
 * This conversion is needed to make full use of the `resque-web` interface
 * included with the original Ruby implementation of Resque.
 */
class RubyNamesPlugin implements EventSubscriberInterface
{
    /**
     * @var Resque
     */
    protected $resque;

    /**
     * Constructor
     */
    public function __construct(Resque $resque, array $options = [])
    {
        $this->resque = $resque;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            JobEvents::AFTER_NORMALIZE    => 'afterNormalize',
            JobEvents::BEFORE_DENORMALIZE => 'beforeDenormalize',
        ];
    }

    /**
     * Translate PHP names to Ruby names
     */
    public function afterNormalize(ResqueEvent $event)
    {
        $item = $event['item'];
        $item['class'] = static::serializeClassName($item['class']);
        $event['item'] = $item;
    }

    /**
     * Translate Ruby names to PHP names
     */
    public function beforeDenormalize(ResqueEvent $event)
    {
        $item = $event['item'];
        $item['class'] = static::deserializeClassName($item['class']);
        $event['item'] = $item;
    }

    /**
     * Prepares a PHP class name for saving it in Resque
     *
     * @param  string $className
     * @return string
     */
    public static function serializeClassName($className)
    {
        return str_replace('\\', '::', $className);
    }

    /**
     * Get the original PHP class name for a class stored in Resque
     *
     * @param  string $className
     * @return string
     */
    public static function deserializeClassName($className)
    {
        return str_replace('::', '\\', $className);
    }
}
