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

namespace Cloud\Job;

use InvalidArgumentException;
use Cloud\Resque\Resque;
use Cloud\Resque\Job;
use Cloud\Resque\Job\PerformInterface;
use Cloud\Resque\Job\Status;
use Cloud\Resque\Scheduler\ResqueScheduler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractJob extends Command
    implements PerformInterface
{
    /**
     * @var Logger;
     */
    protected $logger;

    /**
     * Enqueue a job for immediate execution
     *
     * @param  array   $args   Arguments that should be passed when the job is executed.
     * @param  string  $queue  Name of the queue to place the job in.
     * @return \Resque_Job_Status
     */
    //public static function enqueue($args = [], $queue = Resque::QUEUE_DEFAULT)
    //{
        //$id = Resque::enqueue($queue, get_called_class(), $args, true);
        //return new Status($id);
    //}

    /**
     * Enqueue a job in a given number of seconds from now
     *
     * Identical to enqueue(), however the first argument is the number
     * of seconds before the job should be executed.
     *
     * @param  int    $in     Number of seconds from now when the job should be executed.
     * @param  array  $args   Arguments that should be passed when the job is executed.
     * @param  string $queue  Name of the queue to place the job in.
     * @return \ResqueScheduler\Job\Status
     */
    //public static function enqueueIn($in, $args = [], $queue = Resque::QUEUE_DEFAULT)
    //{
        //$id = ResqueScheduler::enqueueIn($in, $queue, get_called_class(), $args, true);
        //return new Status($id);
    //}

    /**
     * Enqueue a job in a given number of seconds from now
     *
     * Identical to enqueue(), however the first argument is the number
     * of seconds before the job should be executed.
     *
     * @param  DateTime|int $in     DateTime object or int of UNIX timestamp when should be executed.
     * @param  array        $args   Arguments that should be passed when the job is executed.
     * @param  string       $queue  Name of the queue to place the job in.
     * @return \ResqueScheduler\Job\Status
     */
    //public static function enqueueAt($time, $args = [], $queue = Resque::QUEUE_DEFAULT)
    //{
        //$id = ResqueScheduler::enqueueAt($time, $queue, get_called_class(), $args, true);
        //return new Status($id);
    //}

    /**
     * resque: Run job
     */
    public function perform(Job $job)
    {
        $args = $job->getArgs();

        array_unshift($args, $this->getName());
        unset($args['_id']);
        unset($args['_scheduled_at']);

        $input = new ArrayInput($args);
        $input->setInteractive(false);

        $output = new BufferedOutput();

        $code = $this->run($input, $output);
        $message = $output->fetch();

        return $message;
    }

    /**
     * resque: Remove environment for this job
     */
    public function tearDown()
    {
    }
}
