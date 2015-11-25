<?php

namespace Protobuf\Compiler\Command;

use Protobuf\Stream;
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
        $stream   = $this->getStdinStream();
        $hasStdin = $stream->getSize() > 0;

        $this->pluginCommand->setStream($stream);

        return $hasStdin
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
     * @return \Protobuf\Stream
     */
    protected function getStdinStream()
    {
        $handle  = fopen('php://stdin', 'r');
        $stream  = Stream::create();
        $counter = 0;

        stream_set_blocking($handle, false);

        while ( ! feof($handle) && ($counter++ < 10)) {

            $buffer = fread($handle, 1024);
            $length = mb_strlen($buffer, '8bit');

            if ($length > 0) {

                $stream->write($buffer, $length);
                $counter = 0;

                continue;
            }

            usleep(1000);
        }

        $stream->seek(0);
        fclose($handle);

        return $stream;
    }
}
