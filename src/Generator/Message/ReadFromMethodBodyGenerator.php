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
 * Message readFromStream Body Generator
 *
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class ReadFromMethodBodyGenerator extends BaseGenerator
{
    /**
     * @return string[]
     */
    public function generateBody()
    {
        $innerLoop = $this->addIndentation($this->generateInnerLoop(), 1);

        $body[] = '$reader = $context->getReader();';
        $body[] = '$length = $context->getLength();';
        $body[] = '$stream = $context->getStream();';
        $body[] = null;
        $body[] = '$limit = ($length !== null)';
        $body[] = '    ? ($stream->tell() + $length)';
        $body[] = '    : null;';
        $body[] = null;
        $body[] = 'while ($limit === null || $stream->tell() < $limit) {';

        $body = array_merge($body, $innerLoop);

        $body[] = null;
        $body[] = '}';

        return $body;
    }

    /**
     * @return string[]
     */
    protected function generateInnerLoop()
    {
        $body[] = null;
        $body[] = 'if ($stream->eof()) {';
        $body[] = '    break;';
        $body[] = '}';
        $body[] = null;
        $body[] = '$key  = $reader->readVarint($stream);';
        $body[] = '$wire = \Protobuf\WireFormat::getTagWireType($key);';
        $body[] = '$tag  = \Protobuf\WireFormat::getTagFieldNumber($key);';
        $body[] = null;
        $body[] = 'if ($stream->eof()) {';
        $body[] = '    break;';
        $body[] = '}';
        $body[] = null;

        foreach (($this->proto->getFieldList() ?: []) as $field) {
            $lines = $this->generateFieldCondition($field);
            $body  = array_merge($body, $lines);
        }

        $unknowFieldName = $this->getUnknownFieldSetName($this->proto);

        $body[] = 'if ($this->' . $unknowFieldName . ' === null) {';
        $body[] = '    $this->' . $unknowFieldName . ' = new \Protobuf\UnknownFieldSet();';
        $body[] = '}';
        $body[] = null;
        $body[] = '$data    = $reader->readUnknown($stream, $wire);';
        $body[] = '$unknown = new \Protobuf\Unknown($tag, $wire, $data);';
        $body[] = null;
        $body[] = '$this->' . $unknowFieldName . '->add($unknown);';

        return $body;
    }

    /**
     * @param google\protobuf\FieldDescriptorProto $field
     *
     * @return string[]
     */
    protected function generateFieldCondition(FieldDescriptorProto $field)
    {
        $tag   = $field->getNumber();
        $lines = $this->generateFieldReadStatement($field);
        $lines = $this->addIndentation($lines, 1);

        $body[] = 'if ($tag === ' . $tag . ') {';
        $body   = array_merge($body, $lines);
        $body[] = '}';
        $body[] = null;

        return $body;
    }

    /**
     * @param \google\protobuf\FieldDescriptorProto $field
     *
     * @return string[]
     */
    protected function generateFieldReadStatement(FieldDescriptorProto $field)
    {
        $body      = [];
        $reference = null;
        $type      = $field->getType();
        $name      = $field->getName();
        $rule      = $field->getLabel();
        $tag       = $field->getNumber();
        $options   = $field->getOptions();
        $isPack    = $options ? $options->getPacked() : false;

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
            $body[] = 'if ($this->' . $name . ' === null) {';
            $body[] = '    $this->' . $name . ' = new \Protobuf\ScalarCollection();';
            $body[] = '}';
            $body[] = null;
            $body[] = 'while ($stream->tell() < $innerLimit) {';
            $body[] = '    $this->' . $name . '->add(' . $this->generateReadScalarStatement($type->value()) . ');';
            $body[] = '}';
            $body[] = null;
            $body[] = 'continue;';

            return $body;
        }

        if ($type === Type::TYPE_MESSAGE() && $rule === Label::LABEL_REPEATED()) {
            $body[] = '$innerSize    = $reader->readVarint($stream);';
            $body[] = '$innerMessage = new ' . $reference . '();';
            $body[] = null;
            $body[] = 'if ($this->' . $name . ' === null) {';
            $body[] = '    $this->' . $name . ' = new \Protobuf\MessageCollection();';
            $body[] = '}';
            $body[] = null;
            $body[] = sprintf('$this->%s->add($innerMessage);', $name);
            $body[] = null;
            $body[] = '$context->setLength($innerSize);';
            $body[] = '$innerMessage->readFrom($context);';
            $body[] = '$context->setLength($length);';
            $body[] = null;
            $body[] = 'continue;';

            return $body;
        }

        if ($type === Type::TYPE_MESSAGE()) {
            $body[] = '$innerSize  = $reader->readVarint($stream);';
            $body[] = '$innerMessage = new ' . $reference . '();';
            $body[] = null;
            $body[] = sprintf('$this->%s = $innerMessage;', $name);
            $body[] = null;
            $body[] = '$context->setLength($innerSize);';
            $body[] = '$innerMessage->readFrom($context);';
            $body[] = '$context->setLength($length);';
            $body[] = null;
            $body[] = 'continue;';

            return $body;
        }

        if ($type === Type::TYPE_ENUM()) {
            $body[] = sprintf('$this->%s = ' . $reference . '::valueOf(%s);', $name, $this->generateReadScalarStatement($type->value()));
            $body[] = null;
            $body[] = 'continue;';

            return $body;
        }

        if ($rule !== LABEL::LABEL_REPEATED()) {
            $body[] = sprintf('$this->%s = %s;', $name, $this->generateReadScalarStatement($type->value()));
            $body[] = null;
            $body[] = 'continue;';

            return $body;
        }

        $body[] = 'if ($this->' . $name . ' === null) {';
        $body[] = '    $this->' . $name . ' = new \Protobuf\ScalarCollection();';
        $body[] = '}';
        $body[] = null;

        $body[] = sprintf('$this->%s->add(%s);', $name, $this->generateReadScalarStatement($type->value()));
        $body[] = null;
        $body[] = 'continue;';

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
}
