<?php

namespace Protobuf\Compiler\Generator\Message;

use Protobuf\Message;
use Protobuf\WireFormat;
use Protobuf\Configuration;
use Protobuf\Compiler\Options;
use Protobuf\Binary\SizeCalculator;
use Protobuf\Compiler\Generator\BaseGenerator;

use google\protobuf\DescriptorProto;
use google\protobuf\FieldDescriptorProto;
use google\protobuf\FieldDescriptorProto\Type;
use google\protobuf\FieldDescriptorProto\Label;

/**
 * Message serializedSize Body Generator
 *
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class SerializedSizeMethodBodyGenerator extends BaseGenerator
{
    /**
     * @return string[]
     */
    public function generateBody()
    {
        $body[] = '$calculator = $context->getSizeCalculator();';
        $body[] = '$size       = 0;';
        $body[] = null;

        foreach (($this->proto->getFieldList() ?: []) as $field) {
            $lines = $this->generateFieldCondition($field);
            $body  = array_merge($body, $lines, [null]);
        }

        $body[] = 'return $size;';

        return $body;
    }

    /**
     * @param \google\protobuf\FieldDescriptorProto $field
     *
     * @return string[]
     */
    public function generateFieldCondition(FieldDescriptorProto $field)
    {
        $body      = [];
        $fieldName = $field->getName();
        $format    = 'if ($this->%s !== null) {';
        $sttm      = $this->generateFieldSizeStatement($field);
        $lines     = $this->addIndentation($sttm, 1);

        $body[] = sprintf($format, $fieldName);
        $body   = array_merge($body, $lines);
        $body[] = '}';

        return $body;
    }

    /**
     * @param \google\protobuf\FieldDescriptorProto $field
     *
     * @return string[]
     */
    public function generateFieldSizeStatement(FieldDescriptorProto $field)
    {
        $body    = [];
        $name    = $field->getName();
        $type    = $field->getType();
        $rule    = $field->getLabel();
        $tag     = $field->getNumber();
        $options = $field->getOptions();
        $isPack  = $options ? $options->getPacked() : false;

        $wire    = $isPack ? WireFormat::WIRE_LENGTH : WireFormat::getWireType($type->value(), null);
        $key     = WireFormat::getFieldKey($tag, $wire);
        $keySize = $this->getSizeCalculator()->computeVarintSize($key);

        if ($rule === Label::LABEL_REPEATED() && $isPack) {
            $body[] = '$innerSize = 0;';
            $body[] = null;
            $body[] = 'foreach ($this->' . $name . ' as $val) {';
            $body[] = '    $innerSize += ' . $this->generateValueSizeStatement($type->value(), '$val') . ';';
            $body[] = '}';
            $body[] = null;
            $body[] = '$size += ' . $keySize . ';';
            $body[] = '$size += $innerSize;';
            $body[] = '$size += $calculator->computeVarintSize($innerSize);';

            return $body;
        }

        if ($rule === Label::LABEL_REPEATED() && $type !== Type::TYPE_MESSAGE()) {
            $body[] = 'foreach ($this->' . $name . ' as $val) {';
            $body[] = '    $size += ' . $keySize . ';';
            $body[] = '    $size += ' . $this->generateValueSizeStatement($type->value(), '$val') . ';';
            $body[] = '}';

            return $body;
        }

        if ($rule === Label::LABEL_REPEATED()) {
            $body[] = sprintf('foreach ($this->%s as $val) {', $name);
            $body[] = '    $innerSize = $val->serializedSize($context);';
            $body[] = null;
            $body[] = '    $size += ' . $keySize . ';';
            $body[] = '    $size += $innerSize;';
            $body[] = '    $size += $calculator->computeVarintSize($innerSize);';
            $body[] = '}';

            return $body;
        }

        if ($type === Type::TYPE_ENUM()) {
            $body[] = '$size += ' . $keySize . ';';
            $body[] = '$size += ' . $this->generateValueSizeStatement($type->value(), '$this->' . $name . '->value()') . ';';

            return $body;
        }

        if ($type !== Type::TYPE_MESSAGE()) {
            $body[] = '$size += ' . $keySize . ';';
            $body[] = '$size += ' . $this->generateValueSizeStatement($type->value(), '$this->' . $name) . ';';

            return $body;
        }

        $body[] = '$innerSize = $this->' . $name . '->serializedSize($context);';
        $body[] = null;
        $body[] = '$size += ' . $keySize . ';';
        $body[] = '$size += $innerSize;';
        $body[] = '$size += $calculator->computeVarintSize($innerSize);';

        return $body;
    }
}
