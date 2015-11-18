<?php

namespace Protobuf\Compiler\Generator\Message;

use Protobuf\Compiler\Options;
use google\protobuf\DescriptorProto;
use google\protobuf\FieldDescriptorProto;
use Protobuf\Compiler\Generator\BaseGenerator;

/**
 * Message __construct Body Generator
 *
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class ConstructMethodBodyGenerator extends BaseGenerator
{
    /**
     * @return string[]
     */
    public function generateBody()
    {
        $body   = [];
        $fields = $this->proto->getFieldList() ?: [];

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
