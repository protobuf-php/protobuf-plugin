<?php

namespace Protobuf\Compiler\Generator\Message;

use Doctrine\Common\Inflector\Inflector;
use google\protobuf\FieldDescriptorProto;
use Protobuf\Compiler\Generator\BaseGenerator;

/**
 * Message extension Body Generator
 *
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class ExtensionMethodBodyGenerator extends BaseGenerator
{
    /**
     * @param \google\protobuf\FieldDescriptorProto $field
     *
     * @return string[]
     */
    public function generateBody(FieldDescriptorProto $field)
    {
        $fieldName = $field->getName();
        $className = Inflector::classify($fieldName);
        $fqcn      = $this->getNamespace($field->getExtendee() . '.' . $className);

        $body[] = 'return ' . $fqcn . '::extension();';

        return $body;
    }
}
