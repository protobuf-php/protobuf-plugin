<?php

namespace Protobuf\Compiler\Command;

use Protobuf\Compiler;
use Protobuf\Compiler\Protoc\ProcessBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Executes protoc to generate PHP classes
 *
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class GenerateCommand extends Command
{
    /**
     * plugin command
     *
     * @var string
     */
    protected $plugin;

    /**
     * Constructor.
     *
     * @param string $plugin The plugin command
     */
    public function __construct($plugin)
    {
        $this->plugin = $plugin;

        parent::__construct('protobuf:generate');
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('protobuf:generate')
            ->setDescription('Executes protoc to generate PHP classes')
            ->addArgument('protos', InputArgument::IS_ARRAY|InputArgument::REQUIRED, 'proto files')
            ->addOption('generate-imported', null, InputOption::VALUE_NONE, 'Generate imported proto files')
            ->addOption('protoc', null, InputOption::VALUE_REQUIRED, 'protoc compiler executable path', 'protoc')
            ->addOption('out', 'o', InputOption::VALUE_REQUIRED, 'destination directory for generated files', './')
            ->addOption('psr4', null, InputOption::VALUE_IS_ARRAY|InputOption::VALUE_REQUIRED, 'psr-4 base directory')
            ->addOption('include', 'i', InputOption::VALUE_IS_ARRAY|InputOption::VALUE_REQUIRED, 'define an include path')
            ->addOption('include-descriptors', null, InputOption::VALUE_NONE, 'add google-protobuf-proto descriptors to include path');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $args    = [];
        $out     = $input->getOption('out');
        $psr4    = $input->getOption('psr4');
        $protoc  = $input->getOption('protoc');
        $protos  = $input->getArgument('protos');
        $include = $input->getOption('include') ?: [];
        $builder = $this->createProcessBuilder($this->plugin, $protoc);

        if ($output->isVerbose()) {
            $args['verbose'] = 1;
        }

        if ($input->getOption('generate-imported')) {
            $args['generate-imported'] = 1;
        }

        if ($psr4) {
            $args['psr4'] = $psr4;
        }

        if ($input->getOption('include-descriptors')) {
            $builder->setIncludeDescriptors(true);
        }

        $builder->assertVersion();

        $process = $builder->createProcess($out, $protos, $include, $args);
        $command = $process->getCommandLine();

        if ($output->isVerbose()) {
            $output->writeln("Generating protos with protoc -- $command");
        }

        // Run command
        $process->run(function ($type, $buffer) use ($output) {
            if ( ! $output->isVerbose() || ! $buffer) {
                return;
            }

            $output->writeln($buffer);
        });

        $return = $process->getExitCode();
        $result = $process->getOutput();

        if ($return === 0) {
            $output->writeln("<info>PHP classes successfully generate.</info>");

            return $return;
        }

        $output->writeln('<error>protoc exited with an error ('.$return.') when executed with: </error>');
        $output->writeln('');
        $output->writeln('  ' . $command);
        $output->writeln('');
        $output->writeln($result);
        $output->writeln('');
        $output->writeln($process->getErrorOutput());
        $output->writeln('');

        return $return;
    }

    /**
     * @param string $plugin
     * @param string $protoc
     *
     * @return \Protobuf\Compiler\Protoc\ProcessBuilder
     */
    protected function createProcessBuilder($plugin, $protoc)
    {
        return new ProcessBuilder($plugin, $protoc);
    }
}
