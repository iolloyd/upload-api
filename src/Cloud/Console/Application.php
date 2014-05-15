<?php

namespace Cloud\Console;

use Cloud\Silex\ApplicationAwareInterface;

use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Doctrine\ORM\Tools\Console\ConsoleRunner as DoctrineConsoleRunner;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;

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
     * @var Application
     */
    protected $app;

    /**
     * Constructor
     *
     * @param Application $app
     */
    public function __construct(\Cloud\Silex\Application $app, $name, $version)
    {
        $this->app = $app;
        $version = sprintf('0.0.0 (%s)', $app['env']);
 
        parent::__construct($name, $version);

        $this->getDefinition()->addOption(
            new InputOption('--env', 
            null, 
            InputOption::VALUE_REQUIRED, 
            'Silex application mode: development, staging, production', $app['env'])
        );
    }

    /**
     * Adjust Silex configuration before running
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return int
     */
    public function doRun(InputInterface $input, OutputInterface $output)
    {
        /*
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
        */

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

        $helpers->set(new Helper\ApplicationHelper($this->app, 'app'));
        $helpers->set(new EntityManagerHelper($this->app['em'], 'em'));
        $helpers->set(new ConnectionHelper($this->app['db'], 'db'));

        return $helpers;
    }
}
