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

namespace Cloud\Resque\Event;

final class JobEvents
{
    /**
     * The BEFORE_ENQUEUE event occurs before a job is placed on the queue.
     *
     * TODO: document how to cancel job placement
     *
     * The event listener method receives a ResqueEvent with the arguments
     * `job`.
     *
     * @var string
     */
    const BEFORE_ENQUEUE = 'resque.job.before_enqueue';

    /**
     * The AFTER_ENQUEUE event occurs after a job is placed on the queue.
     *
     * The event listener method receives a ResqueEvent with the arguments
     * `job`.
     *
     * @var string
     */
    const AFTER_ENQUEUE = 'resque.job.after_enqueue';

    /**
     * The BEFORE_DESTROY event occurs before a job is destroyed on the queue.
     * This event is called `before_dequeue` in Ruby.
     *
     * The event listener method receives a ResqueEvent with the arguments
     * `job`.
     *
     * @var string
     */
    const BEFORE_DESTROY = 'resque.job.before_destroy';

    /**
     * The AFTER_DESTROY event occurs after a job was destroyed on the queue.
     * This event is called `after_dequeue` in Ruby.
     *
     * The event listener method receives a ResqueEvent with the arguments
     * `job`.
     *
     * @var string
     */
    const AFTER_DESTROY = 'resque.job.after_destroy';

    /**
     * The BEFORE_PERFORM event occurs before a job class' `perform()` method
     * is called.
     *
     * TODO: document how to cancel perform via exception
     *
     * The event listener method receives a ResqueEvent with the arguments
     * `job` and `instance`.
     *
     * @var string
     */
    const BEFORE_PERFORM = 'resque.job.before_perform';

    /**
     * The AFTER_PERFORM event occurs after a job class' `perform()` method
     * finishes.
     *
     * The event listener method receives a ResqueEvent with the arguments
     * `job` and `instance`.
     *
     * @var string
     */
    const AFTER_PERFORM = 'resque.job.after_perform';

    /**
     * The ON_FAILURE event occurs if any exception occurs while performing the
     * job.
     *
     * The event listener method receives a ResqueEvent with the arguments
     * `job` and `exception`.
     *
     * @var string
     */
    const ON_FAILURE = 'resque.job.on_failure';

    // ---

    const BEFORE_NORMALIZE = 'resque.job.before_normalize';
    const AFTER_NORMALIZE = 'resque.job.after_normalize';

    const BEFORE_DENORMALIZE = 'resque.job.before_denormalize';
    const AFTER_DENORMALIZE = 'resque.job.after_denormalize';
}
