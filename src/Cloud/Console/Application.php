<?php

namespace Cloud\Console;

use Cloud\Slim\SlimAwareInterface;
use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Doctrine\ORM\Tools\Console\ConsoleRunner as DoctrineConsoleRunner;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use Slim\Slim;
use Slim\Log as SlimLog;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

/**
 * Console Application
 */
class Application extends BaseApplication
{
    /**
     * @var Slim
     */
    protected $app;

    /**
     * Constructor
     *
     * @param Slim $app
     */
    public function __construct(Slim $app)
    {
        $this->app = $app;

        $name = 'cloud.xxx (cli)';
        $version = sprintf('0.0.0 (%s)', $app->config('mode'));

        parent::__construct($name, $version);

        $this->getDefinition()->addOption(new InputOption('--mode', null, InputOption::VALUE_REQUIRED, 'Slim application mode: development, staging, production', $app->config('mode')));
    }

    /**
     * Adjust Slim configuration before running
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return int
     */
    public function doRun(InputInterface $input, OutputInterface $output)
    {
        $log = $this->app->log;

        if ($output->isQuiet()) {
            $log->setLevel(SlimLog::ERROR);
        } elseif ($output->isVerbose()) {
            $log->setLevel(SlimLog::INFO);
        } elseif ($output->isVeryVerbose()) {
            $log->setLevel(SlimLog::DEBUG);
        } else {
            $log->setLevel(SlimLog::NOTICE);
        }

        foreach ($this->all() as $command) {
            if ($command instanceof SlimAwareInterface) {
                $command->setSlim($this->app);
            }
        }

        return parent::doRun($input, $output);
    }

    /**
     * Gets the default commands that should always be available
     *
     * @return array
     */
    protected function getDefaultCommands()
    {
        $commands = parent::getDefaultCommands();

        // Cloud Commands
        $commands[] = new Command\Development\ServerCommand();
        $commands[] = new Command\Doctrine\LoadFixturesCommand();
        $commands[] = new Command\Resque\StartCommand();
        $commands[] = new Command\Resque\EnqueueCommand();
        $commands[] = new Command\Resque\ScheduleCommand();

        // Jobs
        $finder = new Finder();
        $finder
            ->files()
            ->in('src/Cloud/Job/')
            ->name('*.php')
        ;
        foreach ($finder as $file) {
            $className = 'Cloud\\Job\\' . str_replace('/', '\\', substr($file->getRelativePathname(), 0, -4));
            if (class_exists($className) && is_subclass_of($className, 'Cloud\Job\AbstractJob')) {
                $commands[] = new $className;
            }
        }
        $commands[] = new \CloudOutbound\YouPorn\Job\DemoCombined();

        // Doctrine ORM Commands
        $doctrine = [];
        $doctrine['doctrine:schema:create']   = new \Doctrine\ORM\Tools\Console\Command\SchemaTool\CreateCommand();
        $doctrine['doctrine:schema:update']   = new \Doctrine\ORM\Tools\Console\Command\SchemaTool\UpdateCommand();
        $doctrine['doctrine:schema:drop']     = new \Doctrine\ORM\Tools\Console\Command\SchemaTool\DropCommand();
        $doctrine['doctrine:schema:validate'] = new \Doctrine\ORM\Tools\Console\Command\ValidateSchemaCommand();
        $doctrine['doctrine:mapping:info']    = new \Doctrine\ORM\Tools\Console\Command\InfoCommand();
        $doctrine['doctrine:query:dql']       = new \Doctrine\ORM\Tools\Console\Command\RunDqlCommand();
        $doctrine['doctrine:query:sql']       = new \Doctrine\DBAL\Tools\Console\Command\RunSqlCommand();

        foreach ($doctrine as $name => $command) {
            $command->setName($name);
            $commands[] = $command;
        }

        return $commands;
    }

    /**
     * Gets the default input definition.
     *
     * @return InputDefinition An InputDefinition instance
     */
    protected function getDefaultInputDefinition()
    {
        return new InputDefinition(array(
            new InputArgument('command', InputArgument::REQUIRED, 'The command to execute'),

            new InputOption('--help',           '', InputOption::VALUE_NONE, 'Display this help message.'),
            new InputOption('--verbose',        '-v|vv|vvv', InputOption::VALUE_NONE, 'Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug'),
            new InputOption('--version',        '', InputOption::VALUE_NONE, 'Display the application version.'),
            new InputOption('--ansi',           '',   InputOption::VALUE_NONE, 'Force ANSI output.'),
            new InputOption('--no-interaction', ['--force', '-f'], InputOption::VALUE_NONE, 'Do not ask any interactive question.'),
        ));
    }

    /**
     * Gets the default helper set with the helpers that should always be available.
     *
     * @return HelperSet
     */
    protected function getDefaultHelperSet()
    {
        $helpers = parent::getDefaultHelperSet();

        $helpers->set(new Helper\SlimHelper($this->app), 'slim');
        $helpers->set(new EntityManagerHelper($this->app->em), 'em');
        $helpers->set(new ConnectionHelper($this->app->em->getConnection()), 'db');

        return $helpers;
    }
}
