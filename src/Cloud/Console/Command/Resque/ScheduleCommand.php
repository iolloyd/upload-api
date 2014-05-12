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

use ResqueScheduler\ResqueScheduler;
use ResqueScheduler\Job\Status as JobStatus;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

/**
 * Schedule a resque job at a given time
 */
class ScheduleCommand extends Command
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
                new InputOption('in', null, InputOption::VALUE_REQUIRED, 'Monitor the status of the job until finished.'),
                new InputOption('at', null, InputOption::VALUE_REQUIRED, 'Monitor the status of the job until finished.'),
                new InputOption('track', null, InputOption::VALUE_NONE, 'Monitor the status of the job until finished.'),
                new InputOption('queue', null, InputOption::VALUE_OPTIONAL, 'Name of the queue to place the job in.', 'default'),
            ])
            ->setName('resque:schedule')
            ->setDescription('Schedule a new Resque job')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $formatter = $this->getHelper('formatter');

        $queue = $input->getOption('queue');
        $class = $input->getArgument('class');
        $trackStatus = $input->getOption('track');

        if ($input->hasArgument('args')) {
            $args = $input->getArgument('args');

            if (!is_array($args)) {
                $args = json_decode($input->getArgument('args'), true) ?: [];
            }
        } else {
            $args = [];
        }

        $token = ResqueScheduler::enqueueIn(10, $queue, $class, $args, $trackStatus);

        if ($trackStatus) {
            $status = new JobStatus($token);

            if (!$status->isTracking()) {
                throw new \Exception('Job status is not available for tracking');
            }

            $output->writeln($formatter->formatSection(date('c'), 'tracking status for <comment>job:' . $token . '</comment>'));

            $lastStatus = -1;

            while (true) {
                sleep(1);

                $nextStatus = $status->get();

                if ($nextStatus == $lastStatus) {
                    continue;
                }

                switch ($nextStatus) {
                    case JobStatus::STATUS_SCHEDULED:
                        $output->writeln($formatter->formatSection(date('c'), 'scheduled'));
                        break;

                    case JobStatus::STATUS_WAITING:
                        $output->writeln($formatter->formatSection(date('c'), 'waiting'));
                        break;

                    case JobStatus::STATUS_RUNNING:
                        $output->writeln($formatter->formatSection(date('c'), 'running'));
                        break;

                    case JobStatus::STATUS_FAILED:
                        $failure = \Resque_Failure_Redis::get($token);
                        $this->renderFailure($output, $failure);
                        return 1;

                    case JobStatus::STATUS_COMPLETE:
                        $output->writeln($formatter->formatSection(date('c'), 'complete'));
                        return 0;
                }

                $lastStatus = $nextStatus;
            }
        } else {
            $output->writeln($formatter->formatSection(date('c'), 'enqueued <comment>job:' . $token . '</comment>'));
        }
    }

    /**
     * Render a job failure message
     *
     * @param OutputInterface $output
     * @param array $failure
     */
    protected function renderFailure(OutputInterface $output, array $failure)
    {
        $formatter = $this->getHelper('formatter');

        $lines = [
            '[Job Failed]',
            $failure['exception'] . ': ' . $failure['error'],
        ];

        $output->writeln($formatter->formatSection(date('c'), '<error>failed</error>'));
        $output->writeln('');
        $output->writeln($formatter->formatBlock($lines, 'error', true));
        $output->writeln('');
        $output->writeln('<info>Error Details:</info>');
        $output->writeln(json_encode($failure, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_FORCE_OBJECT));
    }
}
