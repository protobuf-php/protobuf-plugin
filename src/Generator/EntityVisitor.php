<?php

namespace Protobuf\Compiler\Generator;

use Protobuf\Compiler\Entity;

/**
 * entity visitor interface
 *
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
interface EntityVisitor
{
    /**
     * @param \Protobuf\Compiler\Entity $entity
     */
    public function visit(Entity $entity);
}
