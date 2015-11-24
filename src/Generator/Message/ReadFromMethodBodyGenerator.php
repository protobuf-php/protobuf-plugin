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

        $unknowFieldName     = $this->getUniqueFieldName($this->proto, 'unknownFieldSet');
        $extensionsFieldName = $this->getUniqueFieldName($this->proto, 'extensions');

        $body[] = '$extensions = $context->getExtensionRegistry();';
        $body[] = '$extension  = $extensions ? $extensions->findByNumber(self::CLASS, $tag) : null;';
        $body[] = null;
        $body[] = 'if ($extension !== null) {';
        $body[] = '    $this->extensions()->put($extension, $extension->readFrom($context, $wire));';
        $body[] = null;
        $body[] = '    continue;';
        $body[] = '}';
        $body[] = null;
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
        $generator = new ReadFieldStatementGenerator($this->proto, $this->options, $this->package);
        $statement = $generator->generateFieldReadStatement($field);

        return $statement;
    }
}
