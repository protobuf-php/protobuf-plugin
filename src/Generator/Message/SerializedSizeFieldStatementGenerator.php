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
 * Message Field Serialized Size Statement Generator
 *
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class SerializedSizeFieldStatementGenerator extends BaseGenerator
{
    /**
     * @var string
     */
    protected $targetVar;

    /**
     * @param string $variable
     */
    public function setTargetVar($variable)
    {
        $this->targetVar = $variable;
    }

    /**
     * @param \google\protobuf\FieldDescriptorProto $field
     *
     * @return string[]
     */
    public function generateFieldSizeStatement(FieldDescriptorProto $field)
    {
        $body     = [];
        $name     = $field->getName();
        $type     = $field->getType();
        $rule     = $field->getLabel();
        $tag      = $field->getNumber();
        $options  = $field->getOptions();
        $variable = $this->targetVar ?: '$this->' . $name;
        $isPack   = $options ? $options->getPacked() : false;

        $wire    = $isPack ? WireFormat::WIRE_LENGTH : WireFormat::getWireType($type->value(), null);
        $key     = WireFormat::getFieldKey($tag, $wire);
        $keySize = $this->getSizeCalculator()->computeVarintSize($key);

        if ($rule === Label::LABEL_REPEATED() && $isPack) {
            $body[] = '$innerSize = 0;';
            $body[] = null;
            $body[] = 'foreach (' . $variable . ' as $val) {';
            $body[] = '    $innerSize += ' . $this->generateValueSizeStatement($type->value(), '$val') . ';';
            $body[] = '}';
            $body[] = null;
            $body[] = '$size += ' . $keySize . ';';
            $body[] = '$size += $innerSize;';
            $body[] = '$size += $calculator->computeVarintSize($innerSize);';

            return $body;
        }

        if ($rule === Label::LABEL_REPEATED() && $type !== Type::TYPE_MESSAGE()) {
            $body[] = 'foreach (' . $variable . ' as $val) {';
            $body[] = '    $size += ' . $keySize . ';';
            $body[] = '    $size += ' . $this->generateValueSizeStatement($type->value(), '$val') . ';';
            $body[] = '}';

            return $body;
        }

        if ($rule === Label::LABEL_REPEATED()) {
            $body[] = 'foreach (' . $variable . ' as $val) {';
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
            $body[] = '$size += ' . $this->generateValueSizeStatement($type->value(), $variable . '->value()') . ';';

            return $body;
        }

        if ($type !== Type::TYPE_MESSAGE()) {
            $body[] = '$size += ' . $keySize . ';';
            $body[] = '$size += ' . $this->generateValueSizeStatement($type->value(), $variable) . ';';

            return $body;
        }

        $body[] = '$innerSize = ' . $variable . '->serializedSize($context);';
        $body[] = null;
        $body[] = '$size += ' . $keySize . ';';
        $body[] = '$size += $innerSize;';
        $body[] = '$size += $calculator->computeVarintSize($innerSize);';

        return $body;
    }
}
