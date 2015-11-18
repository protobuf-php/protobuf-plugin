<?php

namespace Protobuf\Compiler\Command;

use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Command\Command;

/**
 * Single Command Application
 *
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class Application extends SymfonyApplication
{
    /**
     * @var \Symfony\Component\Console\Command\Command
     */
    private $generateCommand;

    /**
     * @var \Symfony\Component\Console\Command\Command
     */
    private $pluginCommand;

    /**
     * Constructor.
     *
     * @param \Symfony\Component\Console\Command\Command $generateCommand
     * @param \Symfony\Component\Console\Command\Command $pluginCommand
     */
    public function __construct(Command $generateCommand, Command $pluginCommand)
    {
        $this->generateCommand = $generateCommand;
        $this->pluginCommand   = $pluginCommand;

        parent::__construct('protobuf');
    }

    /**
     * {@inheritdoc}
     */
    protected function getCommandName(InputInterface $input)
    {
        return $this->hasStdin()
            ? $this->pluginCommand->getName()
            : $this->generateCommand->getName();
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultCommands()
    {
        $commands   = parent::getDefaultCommands();
        $commands[] = $this->generateCommand;
        $commands[] = $this->pluginCommand;

        return $commands;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        $definition = parent::getDefinition();

        $definition->setArguments();

        return $definition;
    }

    /**
     * @return bool
     */
    protected function hasStdin()
    {
        $stdin = fopen('php://stdin', 'r');

        if ( ! is_resource($stdin)) {
            return false;
        }

        $stats = fstat($stdin);

        fclose($stdin);

        if ( ! isset($stats['size'])) {
            return false;
        }

        return ($stats['size'] > 0);
    }
}
