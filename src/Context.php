<?php

namespace Protobuf\Compiler;

use Protobuf\Configuration;

/**
 * Options given in the command line
 *
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class Context
{
    /**
     * @var \Protobuf\Entity[]
     */
    protected $entities;

    /**
     * @var \Protobuf\Compiler\Options
     */
    protected $options;

    /**
     * @var \Protobuf\Configuration
     */
    protected $configuration;

    /**
     * @param array                      $entities
     * @param \Protobuf\Compiler\Options $options
     * @param \Protobuf\Configuration    $config
     */
    public function __construct(array $entities, Options $options, Configuration $config)
    {
        $this->configuration = $config;
        $this->options       = $options;
        $this->entities      = $entities;
    }

    /**
     * @return \Protobuf\Compiler\Options
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @return array
     */
    public function getEntities()
    {
        return $this->entities;
    }

    /**
     * @return \Protobuf\Configuration
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @param string $fqcn
     *
     * @return \Protobuf\Entity
     */
    public function getEntity($fqcn)
    {
        $class = trim($fqcn, '.');

        if ( ! isset($this->entities[$class])) {
            throw new \LogicException("Unable to find class : $class");
        }

        return $this->entities[$class];
    }
}
