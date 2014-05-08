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

namespace CloudOutbound\YouPorn\Job;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractJob extends Command
{
    //////////////////////////// Resque_Job //////////////////////////////

    /**
     * @var Resque_Job
     */
    public $job;

    /**
     * @var array
     */
    public $args = [];

    /**
     * @var string
     */
    public $queue;

    /**
     * resque: Set up environment for this job
     */
    public function setUp()
    {
    }

    /**
     * resque: Run job
     */
    public function perform()
    {
        $args = $this->args ?: [];
        array_unshift($args, '...');

        $input = new ArrayInput($args);
        $input->setInteractive(false);

        $output = new BufferedOutput();

        $code = $this->run($input, $output);
        $message = $output->fetch();
    }

    /**
     * resque: Remove environment for this job
     */
    public function tearDown()
    {
    }
}
