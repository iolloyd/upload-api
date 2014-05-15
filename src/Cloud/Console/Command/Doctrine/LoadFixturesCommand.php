<?php

namespace Cloud\Console\Command\Doctrine;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader as DataFixturesLoader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;

/**
 * Load doctrine data fixtures
 */
class LoadFixturesCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDefinition([
                new InputArgument(
                    'path',
                    InputArgument::OPTIONAL|InputArgument::IS_ARRAY,
                    'Directory to load data fixtures from.', ['src/Cloud/Model/DataFixtures/']
                ),
                new InputOption(
                    'append',
                    'a',
                    InputOption::VALUE_NONE,
                    'Append the data fixtures instead of deleting all data from the database first.'
                ),
            ])
            ->setName('doctrine:fixtures:load')
            ->setDescription('Load data fixtures to your database')
            ->setHelp(<<<EOT
Loads doctrine data fixtures into the database.

    <info>%command.full_name%</info>

To load fixtures from a particular directory, pass the paths as arguments:

    <info>%command.full_name%</info> <comment>/path/to/fixtures1 /path/to/fixtures2</comment>

By default, all table data is deleted before loading the fixtures. You can
change this behaviour with the <info>append</info> flag:

    <info>%command.full_name%</info> <comment>--append</comment>

EOT
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $conn      = $this->getHelper('db')->getConnection();
        $em        = $this->getHelper('em')->getEntityManager();
        $dialog    = $this->getHelper('dialog');
        $formatter = $this->getHelper('formatter');

        $output->writeln(sprintf(
            'Loading fixtures into <info>%s://%s@%s/%s</info>...',
            $conn->getDatabasePlatform()->getName(),
            $conn->getUsername(),
            $conn->getHost(),
            $conn->getDatabase()
        ));

        $loader = new DataFixturesLoader();
        $paths  = $input->getArgument('path');
        $append = $input->getOption('append');

        foreach ($paths as $path) {
            $loader->loadFromDirectory($path);
        }

        $fixtures = $loader->getFixtures();

        if (!$fixtures) {
            throw new \InvalidArgumentException(
                sprintf('Could not find any fixtures to load in: %s', "\n - " . implode("\n - ", $paths))
            );
        }

        if ($input->isInteractive() && !$input->getOption('append')) {
            if (!$dialog->askConfirmation(
                    $output,
                    '<error>WARNING: All tables will be truncated prior to import.</error> continue? [y/N]: ',
                    false
                )) {
                return 1;
            }
        }

        $output->writeln('');

        $purger = new ORMPurger($em);

        if (!$append) {
            // to truncuate, first delete all database content, then run the
            // actualy purge, to avoid problems with foreign key constraints

            $output->writeln($formatter->formatSection('+', 'truncating database'));
            $conn->exec('SET FOREIGN_KEY_CHECKS = 0');
            $purger->setPurgeMode(ORMPurger::PURGE_MODE_TRUNCATE);
            $purger->purge();
            $conn->exec('SET FOREIGN_KEY_CHECKS = 1');
            $purger->setPurgeMode(ORMPurger::PURGE_MODE_DELETE);
        }

        $executor = new ORMExecutor($em, $purger);
        $executor->setLogger(function($message) use ($output, $formatter) {
            $output->writeln($formatter->formatSection('+', $message));
        });
        $executor->execute($fixtures, $append);

        $output->writeln('');
    }
}
