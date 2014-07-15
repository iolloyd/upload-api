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

use InvalidArgumentException;
use Cloud\Resque\Job;
use Cloud\Resque\Resque;
use Cloud\Resque\Plugin\Status\Job\Status;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

/**
 * Enqueue a resque job
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
                new InputOption('monitor', null, InputOption::VALUE_NONE, 'Monitor the status of the job until finished.'),
                new InputOption('queue', null, InputOption::VALUE_OPTIONAL, 'Name of the queue to place the job in.', 'default'),
            ])
            ->setName('resque:enqueue')
            ->setDescription('Enqueue a new Resque job')
            ->setHelp(<<<EOT
Enqueue a new Resque job.

    <info>%command.full_name% 'My\Job\DoSomething' '{"foo":"bar"}' --queue=high --monitor</info>

EOT
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app = $this->getHelper('silex')->getApplication();
        $formatter = $this->getHelper('formatter');

        // params

        $queue = $input->getOption('queue');
        $class = $input->getArgument('class');
        $monitorStatus = $input->getOption('monitor');

        if ($input->hasArgument('args')) {
            $args = $input->getArgument('args');

            if (!is_array($args)) {
                $args = json_decode($input->getArgument('args'), true);
            }
        } else {
            $args = null;
        }

        if (!class_exists($class)) {
            throw new InvalidArgumentException(sprintf('Job class %s does not exist', $class));
        } elseif (!is_subclass_of($class, 'Cloud\Job\AbstractJob')) {
            throw new InvalidArgumentException(sprintf('Job class %s does not extend Cloud\Job\AbstractJob', $class));
        }

        // enqueue

        $resque = $app['resque'];

        if ($queue) {
            $job = $resque->enqueueTo($queue, $class, $args);
        } else {
            $job = $resque->enqueue($class, $args);
        }

        $output->writeln($formatter->formatSection(date('c'), 'enqueued job: <comment>' . $job . '</comment>'));

        // monitor status

        if ($monitorStatus) {
            $output->writeln($formatter->formatSection(date('c'), 'monitoring job status:'));

            $lastStatus = null;

            while (true) {
                $status = $app['resque.status']($job);
                $nextStatus = $status->getStatus();

                if ($nextStatus == $lastStatus) {
                    continue;
                }

                if (!$status->hasPerformed()) {
                    $output->writeln($formatter->formatSection(date('c'), ' + ' . $status->getStatus()));
                } elseif ($status->hasError()) {
                    $output->writeln($formatter->formatSection(date('c'), ' + <error>' . $status->getStatus() . '</error>'));
                    $this->renderFailure($output, $status);
                    return 1;
                } else {
                    $output->writeln($formatter->formatSection(date('c'), ' + <fg=green>' . $status->getStatus() . '</fg=green>'));
                    return 0;
                }

                $lastStatus = $nextStatus;

                sleep(1);
            }
        }

        return 0;
    }

    /**
     * Render a job failure message
     *
     * @param OutputInterface $output
     * @param array $failure
     */
    protected function renderFailure(OutputInterface $output, Status $status)
    {
        $formatter = $this->getHelper('formatter');

        $lines = [
            $status->getMessage(),
        ];

        $output->writeln('');
        $output->writeln($formatter->formatBlock($lines, 'error', true));
        $output->writeln('');

        //$output->writeln('<info>Error Details:</info>');
        //$output->writeln(json_encode($failure, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_FORCE_OBJECT));
    }
}
