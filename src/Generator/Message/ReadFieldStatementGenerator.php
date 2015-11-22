<?php

namespace Protobuf\Compiler\Generator\Message;

use Protobuf\WireFormat;
use InvalidArgumentException;
use Protobuf\Compiler\Options;
use google\protobuf\DescriptorProto;
use google\protobuf\FieldDescriptorProto;
use google\protobuf\FieldDescriptorProto\Type;
use google\protobuf\FieldDescriptorProto\Label;
use Protobuf\Compiler\Generator\BaseGenerator;

/**
 * Message Field Read Statement Generator
 *
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class ReadFieldStatementGenerator extends BaseGenerator
{
    const BREAK_MODE_CONTINUE = 1;
    const BREAK_MODE_RETURN   = 2;

    /**
     * @var string
     */
    protected $breakMode = self::BREAK_MODE_CONTINUE;

    /**
     * @var string
     */
    protected $targetVar;

    /**
     * @param integer $mode
     */
    public function setBreakMode($mode)
    {
        $this->breakMode = $mode;
    }

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
    public function generateFieldReadStatement(FieldDescriptorProto $field)
    {
        $body      = [];
        $reference = null;
        $type      = $field->getType();
        $name      = $field->getName();
        $rule      = $field->getLabel();
        $tag       = $field->getNumber();
        $options   = $field->getOptions();
        $isPack    = $options ? $options->getPacked() : false;
        $variable  = $this->targetVar ?: '$this->' . $name;
        $breakSttm = $this->getBreakStatement($variable);

        if ($field->hasTypeName()) {
            $typeName  = $field->getTypeName();
            $reference = $this->getNamespace($typeName);
        }

        if ( ! $isPack) {
            $body[] = sprintf('\Protobuf\WireFormat::assertWireType($wire, %s);', $type->value());
            $body[] = null;
        }

        if ($rule === Label::LABEL_REPEATED() && $isPack) {
            $body[] = '$innerSize  = $reader->readVarint($stream);';
            $body[] = '$innerLimit = $stream->tell() + $innerSize;';
            $body[] = null;
            $body[] = 'if (' . $variable . ' === null) {';
            $body[] = '    ' . $variable . ' = new \Protobuf\ScalarCollection();';
            $body[] = '}';
            $body[] = null;
            $body[] = 'while ($stream->tell() < $innerLimit) {';
            $body[] = '    ' . $variable . '->add(' . $this->generateReadScalarStatement($type->value()) . ');';
            $body[] = '}';
            $body[] = null;
            $body[] = $breakSttm;

            return $body;
        }

        if ($type === Type::TYPE_MESSAGE() && $rule === Label::LABEL_REPEATED()) {
            $body[] = '$innerSize    = $reader->readVarint($stream);';
            $body[] = '$innerMessage = new ' . $reference . '();';
            $body[] = null;
            $body[] = 'if (' . $variable . ' === null) {';
            $body[] = '    ' . $variable . ' = new \Protobuf\MessageCollection();';
            $body[] = '}';
            $body[] = null;
            $body[] = $variable . '->add($innerMessage);';
            $body[] = null;
            $body[] = '$context->setLength($innerSize);';
            $body[] = '$innerMessage->readFrom($context);';
            $body[] = '$context->setLength($length);';
            $body[] = null;
            $body[] = $breakSttm;

            return $body;
        }

        if ($type === Type::TYPE_MESSAGE()) {
            $body[] = '$innerSize    = $reader->readVarint($stream);';
            $body[] = '$innerMessage = new ' . $reference . '();';
            $body[] = null;
            $body[] = $variable . ' = $innerMessage;';
            $body[] = null;
            $body[] = '$context->setLength($innerSize);';
            $body[] = '$innerMessage->readFrom($context);';
            $body[] = '$context->setLength($length);';
            $body[] = null;
            $body[] = $breakSttm;

            return $body;
        }

        if ($type === Type::TYPE_ENUM()) {
            $body[] = $variable . ' = ' . $reference . '::valueOf(' . $this->generateReadScalarStatement($type->value()) . ');';
            $body[] = null;
            $body[] = $breakSttm;

            return $body;
        }

        if ($rule !== LABEL::LABEL_REPEATED()) {
            $body[] = $variable . ' = ' . $this->generateReadScalarStatement($type->value()) . ';';
            $body[] = null;
            $body[] = $breakSttm;

            return $body;
        }

        $body[] = 'if (' . $variable . ' === null) {';
        $body[] = '    ' . $variable . ' = new \Protobuf\ScalarCollection();';
        $body[] = '}';
        $body[] = null;
        $body[] = $variable . '->add(' . $this->generateReadScalarStatement($type->value()) . ');';
        $body[] = null;
        $body[] = $breakSttm;

        return $body;
    }

    /**
     * read a scalar value.
     *
     * @param integer $type
     *
     * @return string
     */
    protected function generateReadScalarStatement($type)
    {
        $mapping = [
            Type::TYPE_INT32_VALUE    => '$reader->readVarint($stream)',
            Type::TYPE_INT64_VALUE    => '$reader->readVarint($stream)',
            Type::TYPE_UINT64_VALUE   => '$reader->readVarint($stream)',
            Type::TYPE_UINT32_VALUE   => '$reader->readVarint($stream)',
            Type::TYPE_DOUBLE_VALUE   => '$reader->readDouble($stream)',
            Type::TYPE_FIXED64_VALUE  => '$reader->readFixed64($stream)',
            Type::TYPE_SFIXED64_VALUE => '$reader->readSFixed64($stream)',
            Type::TYPE_FLOAT_VALUE    => '$reader->readFloat($stream)',
            Type::TYPE_FIXED32_VALUE  => '$reader->readFixed32($stream)',
            Type::TYPE_SFIXED32_VALUE => '$reader->readSFixed32($stream)',
            Type::TYPE_ENUM_VALUE     => '$reader->readVarint($stream)',
            Type::TYPE_SINT32_VALUE   => '$reader->readZigzag($stream)',
            Type::TYPE_SINT64_VALUE   => '$reader->readZigzag($stream)',
            Type::TYPE_BOOL_VALUE     => '$reader->readBool($stream)',
            Type::TYPE_STRING_VALUE   => '$reader->readString($stream)',
            Type::TYPE_BYTES_VALUE    => '$reader->readBytes($stream)',
        ];

        if (isset($mapping[$type])) {
            return $mapping[$type];
        }

        throw new InvalidArgumentException('Unknown field type : ' . $type);
    }

    /**
     * @param string $variable
     *
     * @return string
     */
    protected function getBreakStatement($variable)
    {
        if ($this->breakMode === self::BREAK_MODE_CONTINUE) {
            return 'continue;';
        }

        return 'return ' . $variable . ';';
    }
}
