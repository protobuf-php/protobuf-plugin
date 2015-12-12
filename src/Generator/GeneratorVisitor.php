<?php

namespace Protobuf\Compiler\Generator;

use Protobuf\Compiler\Entity;
use Zend\Code\Generator\GeneratorInterface;

/**
 * entity class generator visitor interface
 *
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
interface GeneratorVisitor
{
    /**
     * @param \Protobuf\Compiler\Entity               $entity
     * @param \Zend\Code\Generator\GeneratorInterface $class
     */
    public function visit(Entity $entity, GeneratorInterface $class);
}
