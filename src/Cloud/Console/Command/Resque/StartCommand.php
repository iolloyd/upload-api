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
 * Start resque worker processes
 */
class StartCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDefinition([
                new InputOption('queue', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'A list of queues name polled by the worker.', ['default']),
                new InputOption('interval', null, InputOption::VALUE_REQUIRED, 'Polling frequency. Number of seconds between each polling.', 1),

                //new InputOption('daemon', null, InputOption::VALUE_REQUIRED, 'Append the data fixtures instead of deleting all data from the database first.', false),
                //new InputOption('pid', 'p', InputOption::VALUE_OPTIONAL, 'Append the data fixtures instead of deleting all data from the database first.'),

                new InputOption('workers', null, InputOption::VALUE_REQUIRED, 'Number of Resque workers to start in parallel.', 3),
                new InputOption('prefix', null, InputOption::VALUE_OPTIONAL, 'Use a custom prefix to separate multiple apps using Resque', 'resque'),

                new InputOption('no-blocking', '', InputOption::VALUE_NONE, 'Do not use BLPOP when polling the queue.'),
                new InputOption('scheduler', '', InputOption::VALUE_NONE, 'Run a resque-scheduler instance.'),
            ])
            ->setName('resque:start')
            ->setDescription('Start up a Resque worker')
            ->setHelp(<<<EOT
Starts up a Resque worker.

    <info>%command.full_name%</info>

Use the <info>--queue</info> option to specify which queues this worker should
poll. If passed a single "*", this Worker will operate on all queues
in alphabetical order.

If multiple queues are given, the worker will check the first queue for a job,
then the second, and so on. This means that queues are picked off in order of
their priority. A job from a lower priority queue will only be picked off if
there are no jobs for a higher priority queue available.

    <info>%command.full_name%</info> <comment>--queue=high,medium,low</comment>

EOT
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $count = $input->getOption('workers');

        /*
         * a) child, run resque worker
         */

        if (getenv('__RESQUE_RUN__')) {
            if (class_exists('\Resque_Log')) {
                // php-resque
                return $this->runPhpResque($input, $output);
            } else {
                // php-resque-ex
                return $this->runPhpResqueEx($input, $output);
            }

            return;
        }

        /*
         * b) parent, spawn child processes
         */

        if ($input->hasOption('pid')) {
            file_put_contents($input->getOption('pid'), getmypid());
        }

        // signals

        pcntl_signal(SIGQUIT, function () use ($output) {
            $output->writeln('<info>Wait for jobs to finish processing then exit...</info>');
        });
        pcntl_signal(SIGINT, function () use ($output) {
            $output->writeln('<info>Immediately kill jobs then exit...</info>');
        });
        pcntl_signal(SIGTERM, function () use ($output) {
            $output->writeln('<info>Immediately kill jobs then exit...</info>');
        });
        pcntl_signal(SIGUSR1, function () use ($output) {
            $output->writeln('<info>Immediately kill jobs but keep runing...</info>');
        });
        pcntl_signal(SIGUSR2, function () use ($output) {
            $output->writeln('<info>Pause workers, no new jobs will be processed...</info>');
        });
        pcntl_signal(SIGCONT, function () use ($output) {
            $output->writeln('<info>Resume workers...</info>');
        });

        // build processes

        $builder = new ProcessBuilder(array_merge([PHP_BINARY], $_SERVER['argv']));
        $builder
            ->inheritEnvironmentVariables(true)
            ->setEnv('__RESQUE_RUN__', 'true')
            ->setTimeout(null);

        $processes = [];

        for ($i = 0; $i < $count; ++$i) {
            $processes[] = $builder->getProcess();
        }

        if (class_exists('\ResqueScheduler\ResqueScheduler')) {
            $builder->add('--scheduler');
            $processes[] = $builder->getProcess();
        }

        // process loop

        $exitCode = 0;

        while (count($processes) > 0) {
            pcntl_signal_dispatch();

            foreach ($processes as $i => $process) {
                if (!$process->isStarted()) {
                    $process->start();
                }

                $output->write($process->getIncrementalOutput());
                $output->write('<error>' . $process->getIncrementalErrorOutput() . '</error>');

                if ($process->isTerminated()) {
                    $exitCode = max($exitCode, $process->getExitCode());
                }

                if ($process->isTerminated() && !$process->isSuccessful()) {
                    array_walk($processes, function ($process) {
                        $process->stop();
                    });
                }

                if (!$process->isRunning()) {
                    unset($processes[$i]);
                }
            }

            usleep(0.100 * 1000000);
        }

        if ($input->hasOption('pid')) {
            unlink($input->getOption('pid'));
        }

        return $exitCode;
    }

    /**
     * Run standard `php-resque`
     * https://github.com/chrisboulton/php-resque/
     */
    protected function runPhpResque(InputInterface $input, OutputInterface $output)
    {
        $logger = new \Resque_Log($output->isVerbose());

        if ($input->hasOption('prefix')) {
            \Resque_Redis::prefix($input->getOption('prefix'));
        }

        $queues   = $input->getOption('queue');
        $interval = max(1, round($input->getOption('interval')));
        $blocking = !$input->getOption('no-blocking');

        $worker = new \Resque_Worker($queues);
        $worker->setLogger($logger);

        \Resque_Event::listen('beforePerform', function ($job) {
            $instance = $job->getInstance();

            if ($instance instanceof Command) {
                $instance->setApplication($this->getApplication());
                $instance->mergeApplicationDefinition(false);
            }
        });

        $logger->log(\Psr\Log\LogLevel::NOTICE, 'Starting worker {worker}', array('worker' => $worker));
        $worker->work($interval, $blocking);
    }

    /**
     * Run forked `php-resque-ex` used with ResqueBoard
     *
     * php-resque-ex is a port of php-resque with more logging options,
     * essential to send log to Cube via an UDP socket.
     *
     * https://github.com/kamisama/php-resque-ex
     */
    protected function runPhpResqueEx(InputInterface $input, OutputInterface $output)
    {
        if ($input->hasOption('prefix')) {
            \Resque::setBackend(null, 0, $input->getOption('prefix'));
        }

        $queues   = $input->getOption('queue');
        $interval = max(5, round($input->getOption('interval')));

        // logger

        $logger = new \MonologInit\MonologInit('Cube', 'udp://127.0.0.1:1180');

        if ($output->isVeryVerbose()) {
            $logLevel = \Resque_Worker::LOG_VERBOSE;
        } else {
            $logLevel = \Resque_Worker::LOG_NORMAL;
        }

        if ($output->isVerbose()) {
            $stdoutHandler = new \Monolog\Handler\StreamHandler('php://stdout');
            $logger->getInstance()->pushHandler($stdoutHandler);
        }

        // start

        if (!$input->getOption('scheduler')) {
            $worker = new \Resque_Worker($queues);
        } else {
            $logger->getInstance()->addInfo('Starting resque-scheduler worker...');
            $worker = new \ResqueScheduler\Worker([ \ResqueScheduler\ResqueScheduler::QUEUE_NAME ]);
        }

        $worker->registerLogger($logger);
        $worker->logLevel = $logLevel;

        \Resque_Event::listen('beforePerform', function ($job) {
            $instance = $job->getInstance();

            if ($instance instanceof Command) {
                $instance->setApplication($this->getApplication());
                $instance->mergeApplicationDefinition(false);
            }
        });

        $worker->work($interval);
    }
}
