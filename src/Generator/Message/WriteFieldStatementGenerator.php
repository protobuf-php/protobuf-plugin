<?php

namespace Protobuf\Compiler\Generator\Message;

use Protobuf\WireFormat;
use Protobuf\Compiler\Options;
use google\protobuf\DescriptorProto;
use google\protobuf\FieldDescriptorProto;
use google\protobuf\FieldDescriptorProto\Type;
use google\protobuf\FieldDescriptorProto\Label;
use Protobuf\Compiler\Generator\BaseGenerator;

/**
 * Message Field Write Statement Generator
 *
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class WriteFieldStatementGenerator extends BaseGenerator
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
    public function generateFieldWriteStatement(FieldDescriptorProto $field)
    {
        $body     = [];
        $name     = $field->getName();
        $type     = $field->getType();
        $rule     = $field->getLabel();
        $tag      = $field->getNumber();
        $options  = $field->getOptions();
        $isPack   = $options ? $options->getPacked() : false;
        $variable = $this->targetVar ?: '$this->' . $name;

        $wire = $isPack ? WireFormat::WIRE_LENGTH : WireFormat::getWireType($type->value(), null);
        $key  = WireFormat::getFieldKey($tag, $wire);

        if ($rule === Label::LABEL_REPEATED() && $isPack) {
            $body[] = '$innerSize   = 0;';
            $body[] = '$calculator  = $sizeContext->getSizeCalculator();';
            $body[] = null;
            $body[] = 'foreach (' . $variable . ' as $val) {';
            $body[] = '    $innerSize += ' . $this->generateValueSizeStatement($type->value(), '$val') . ';';
            $body[] = '}';
            $body[] = null;
            $body[] = '$writer->writeVarint($stream, ' . $key . ');';
            $body[] = '$writer->writeVarint($stream, $innerSize);';
            $body[] = null;
            $body[] = 'foreach (' . $variable . ' as $val) {';
            $body[] = '    ' . $this->generateWriteScalarStatement($type->value(), '$val') . ';';
            $body[] = '}';

            return $body;
        }

        if ($rule === Label::LABEL_REPEATED() && $type !== Type::TYPE_MESSAGE()) {
            $body[] = 'foreach (' . $variable . ' as $val) {';
            $body[] = '    $writer->writeVarint($stream, ' . $key . ');';
            $body[] = '    ' . $this->generateWriteScalarStatement($type->value(), '$val') . ';';
            $body[] = '}';

            return $body;
        }

        if ($rule === Label::LABEL_REPEATED()) {
            $body[] = 'foreach (' . $variable . ' as $val) {';
            $body[] = '    $writer->writeVarint($stream, ' . $key . ');';
            $body[] = '    $writer->writeVarint($stream, $val->serializedSize($sizeContext));';
            $body[] = '    $val->writeTo($context);';
            $body[] = '}';

            return $body;
        }

        if ($type === Type::TYPE_ENUM()) {
            $body[] = sprintf('$writer->writeVarint($stream, %s);', $key);
            $body[] = $this->generateWriteScalarStatement($type->value(), $variable . '->value()') . ';';

            return $body;
        }

        if ($type !== Type::TYPE_MESSAGE()) {
            $body[] = sprintf('$writer->writeVarint($stream, %s);', $key);
            $body[] = $this->generateWriteScalarStatement($type->value(), $variable) . ';';

            return $body;
        }

        $body[] = '$writer->writeVarint($stream, ' . $key . ');';
        $body[] = '$writer->writeVarint($stream, ' . $variable . '->serializedSize($sizeContext));';
        $body[] = $variable . '->writeTo($context);';

        return $body;
    }

    /**
     * write a scalar value.
     *
     * @param integer $type
     * @param string  $value
     * @param string  $stream
     *
     * @return array
     */
    public function generateWriteScalarStatement($type, $value, $stream = '$stream')
    {
        $mapping = [
            Type::TYPE_INT32_VALUE    => '$writer->writeVarint(%s, %s)',
            Type::TYPE_INT64_VALUE    => '$writer->writeVarint(%s, %s)',
            Type::TYPE_UINT64_VALUE   => '$writer->writeVarint(%s, %s)',
            Type::TYPE_UINT32_VALUE   => '$writer->writeVarint(%s, %s)',
            Type::TYPE_DOUBLE_VALUE   => '$writer->writeDouble(%s, %s)',
            Type::TYPE_FIXED64_VALUE  => '$writer->writeFixed64(%s, %s)',
            Type::TYPE_SFIXED64_VALUE => '$writer->writeSFixed64(%s, %s)',
            Type::TYPE_FLOAT_VALUE    => '$writer->writeFloat(%s, %s)',
            Type::TYPE_FIXED32_VALUE  => '$writer->writeFixed32(%s, %s)',
            Type::TYPE_SFIXED32_VALUE => '$writer->writeSFixed32(%s, %s)',
            Type::TYPE_ENUM_VALUE     => '$writer->writeVarint(%s, %s)',
            Type::TYPE_SINT32_VALUE   => '$writer->writeZigzag32(%s, %s)',
            Type::TYPE_SINT64_VALUE   => '$writer->writeZigzag64(%s, %s)',
            Type::TYPE_BOOL_VALUE     => '$writer->writeBool(%s, %s)',
            Type::TYPE_STRING_VALUE   => '$writer->writeString(%s, %s)',
            Type::TYPE_BYTES_VALUE    => '$writer->writeString(%s, %s)',
        ];

        if (isset($mapping[$type])) {
            return sprintf($mapping[$type], $stream, $value);
        }

        throw new \InvalidArgumentException('Unknown field type : ' . $type);
    }
}
