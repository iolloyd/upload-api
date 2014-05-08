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

namespace Cloud\Console\Command\Resque;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

/**
 * Enqueue a Resque job
 */
class EnqueueCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDefinition([
                new InputArgument('class', InputArgument::REQUIRED, 'Name of the class that contains the code to execute the job.'),
                new InputArgument('args', InputArgument::OPTIONAL, 'A JSON object of arguments to pass to the job.'),
                new InputOption('track', null, InputOption::VALUE_NONE, 'Monitor the status of the job until finished.'),
                new InputOption('queue', null, InputOption::VALUE_OPTIONAL, 'Name of the queue to place the job in.', 'default'),
                new InputOption('prefix', null, InputOption::VALUE_OPTIONAL, 'Use a custom prefix to separate multiple apps using Resque', 'resque'),
            ])
            ->setName('resque:enqueue')
            ->setDescription('Enqueue a new Resque job')
            ->setHelp(<<<EOT
Enqueue a new Resque job.

    <info>%command.full_name% 'My\Job\DoSomething' '{"foo":"bar"}' --queue=high --track</info>

EOT
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getApplication()->setCatchExceptions(false);

        $queue       = $input->getOption('queue');
        $class       = $input->getArgument('class');
        $trackStatus = $input->getOption('track');

        if ($input->hasArgument('args')) {
            $args = $input->getArgument('args');

            if (!is_array($args)) {
                $args = json_decode($input->getArgument('args'), true);
            }
        } else {
            $args = null;
        }

        // enqueue

        $token = \Resque::enqueue($queue, $class, $args, $trackStatus);

        echo $token;
    }
}
