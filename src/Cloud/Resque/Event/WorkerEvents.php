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

final class WorkerEvents
{
    /**
     * The BEFORE_FIRST_FORK event occurs right after a worker is
     * started and before the worker registers and forks for the first time.
     *
     * The event listener method receives a ResqueEvent with the arguments
     * `worker`.
     *
     * @var string
     */
    const BEFORE_FIRST_FORK = 'resque.worker.before_first_fork';

    /**
     * The BEFORE_FORK event occurs before a worker forks to process a job.
     *
     * The listener method will be run in the parent process. So, be careful,
     * any changes you make will be permanent for the lifespan of the worker.
     *
     * The event listener method receives a ResqueEvent with the arguments
     * `worker` and `job`.
     *
     * @var string
     */
    const BEFORE_FORK = 'resque.worker.before_fork';

    /**
     * The AFTER_FORK event occurs after a worker forks to process a job but
     * before the job is processed.
     *
     * The listener method will be run in the child process and is passed the
     * current job. Any changes you make, therefor, will only live as long as
     * the job currently being processed.
     *
     * The event listener method receives a ResqueEvent with the arguments
     * `worker` and `job`.
     *
     * @var string
     */
    const AFTER_FORK = 'resque.worker.after_fork';

    /**
     * The BEFORE_PAUSE event occurs when a worker pauses processing new jobs.
     *
     * The event listener method receives a ResqueEvent with the arguments
     * `worker`.
     *
     * @var string
     */
    const BEFORE_PAUSE = 'resque.worker.before_pause';

    /**
     * The AFTER_PAUSE event occurs when a worker is unpaused and resumes
     * processing new jobs.
     *
     * The event listener method receives a ResqueEvent with the arguments
     * `worker`.
     *
     * @var string
     */
    const AFTER_PAUSE = 'resque.worker.after_pause';
}
