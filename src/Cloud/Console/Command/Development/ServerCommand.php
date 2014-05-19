<?php

namespace Cloud\Console\Command\Development;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

/**
 * Runs PHP built-in web server
 */
class ServerCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDefinition([
                new InputArgument('address', InputArgument::OPTIONAL, 'address:port', '0.0.0.0:8080'),
            ])
            ->setName('development:server')
            ->setDescription('Runs PHP built-in web server')
            ->setHelp(<<<EOF
Runs a PHP built-in web server for development.

    <info>{$_SERVER['PHP_SELF']} dev:serv</info>
    <info>%command.full_name%</info>

By default, detailed request information is hidden. To show verbose output, run:

    <info>%command.full_name%</info> <comment>-v</comment>

To change the address and port the server is running on, pass them as the first
argument:

    <info>%command.full_name%</info> <comment>127.0.0.1:8080</comment>

See also: http://www.php.net/manual/en/features.commandline.webserver.php
EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app = $this->getHelper('silex')->getApplication();

        if ($app['env'] != 'development') {
            $output->writeln('<error>Running PHP built-in server in production environment is NOT recommended!</error>');
        }

        $output->writeln(sprintf('Server running on <info>http://%s</info>...', $input->getArgument('address')));

        $builder = new ProcessBuilder([
            PHP_BINARY,
            '-S', $input->getArgument('address'),
            '-t', 'public/',
            'public/index.php',
        ]);
        $builder->setTimeout(null);

        $process = $builder->getProcess();
        $process->run(function ($type, $message) use ($output) {
            if ($type == Process::ERR) {
                $output->write('<error>' . $message . '</error>');
            } elseif ($output->isVerbose()) {
                $output->write($message);
            }
        });

        return $process->getExitCode();
    }
}

