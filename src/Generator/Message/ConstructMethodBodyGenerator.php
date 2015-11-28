<?php

namespace Protobuf\Compiler\Generator\Message;

use Protobuf\Compiler\Entity;
use Protobuf\Compiler\Generator\BaseGenerator;

/**
 * Message __construct Body Generator
 *
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class ConstructMethodBodyGenerator extends BaseGenerator
{
    /**
     * @param \Protobuf\Compiler\Entity $entity
     *
     * @return string[]
     */
    public function generateBody(Entity $entity)
    {
        $body       = [];
        $descriptor = $entity->getDescriptor();
        $fields     = $descriptor->getFieldList() ?: [];

        foreach ($fields as $field) {
            if ( ! $field->hasDefaultValue()) {
                continue;
            }

            $name  = $field->getName();
            $value = $this->getDefaultFieldValue($field);

            $body[] = sprintf('$this->%s = %s;', $name, $value);
        }

        return $body;
    }
}
