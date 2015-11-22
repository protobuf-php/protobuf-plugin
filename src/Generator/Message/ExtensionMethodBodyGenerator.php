<?php

namespace Protobuf\Compiler\Generator\Message;

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
        $name          = $field->getName();
        $tag           = $field->getNumber();
        $nameQuoted    = var_export($name, true);
        $readCallback  = $this->generateReadCallback($field);
        $writeCallback = $this->generateWriteCallback($field);
        $arguments     = implode(', ', [$nameQuoted, $tag, '$readCallback', '$writeCallback']);

        $body[] = 'if (self::$' . $name . ' !== null) {';
        $body[] = '    return self::$' . $name . ';';
        $body[] = '}';
        $body[] = null;
        $body   = array_merge($body, $readCallback, [null], $writeCallback);
        $body[] = null;
        $body[] = 'return self::$' . $name . ' = new \Protobuf\Extension(' . $arguments . ');';

        return $body;
    }

    /**
     * @param \google\protobuf\FieldDescriptorProto $field
     *
     * @return string[]
     */
    protected function generateReadCallback(FieldDescriptorProto $field)
    {
        $body  = [];
        $sttm  = $this->generateFieldReadStatement($field);
        $lines = $this->addIndentation($sttm, 1);

        $body[] = '$readCallback = function (\Protobuf\ReadContext $context, $key, $wire, $tag) {';
        $body[] = '    $reader = $context->getReader();';
        $body[] = '    $length = $context->getLength();';
        $body[] = '    $stream = $context->getStream();';
        $body[] = null;
        $body   = array_merge($body, $lines);
        $body[] = '};';

        return $body;
    }

    /**
     * @param \google\protobuf\FieldDescriptorProto $field
     *
     * @return string[]
     */
    protected function generateWriteCallback(FieldDescriptorProto $field)
    {
        $body  = [];
        $sttm  = $this->generateFieldWriteStatement($field);
        $lines = $this->addIndentation($sttm, 1);

        $body[] = '$writeCallback = function ($value, \Protobuf\WriteContext $context) {';
        $body[] = '    $stream      = $context->getStream();';
        $body[] = '    $writer      = $context->getWriter();';
        $body[] = '    $sizeContext = $context->getComputeSizeContext();';
        $body[] = null;
        $body   = array_merge($body, $lines);
        $body[] = '};';

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

        $generator->setBreakMode(ReadFieldStatementGenerator::BREAK_MODE_RETURN);
        $generator->setTargetVar('$value');

        return $generator->generateFieldReadStatement($field);
    }

    /**
     * @param \google\protobuf\FieldDescriptorProto $field
     *
     * @return string[]
     */
    protected function generateFieldWriteStatement(FieldDescriptorProto $field)
    {
        $generator = new WriteFieldStatementGenerator($this->proto, $this->options, $this->package);

        $generator->setTargetVar('$value');

        return $generator->generateFieldWriteStatement($field);
    }
}
