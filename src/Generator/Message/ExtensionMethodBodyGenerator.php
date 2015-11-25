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
        $name     = $field->getName();
        $tag      = $field->getNumber();
        $extendee = $this->getNamespace($field->getExtendee());

        $nameQuoted     = var_export($name, true);
        $extendeeQuoted = var_export($extendee, true);

        $sizeCallback  = $this->generateSizeCallback($field);
        $readCallback  = $this->generateReadCallback($field);
        $writeCallback = $this->generateWriteCallback($field);
        $callbacks     = array_merge($readCallback, [null], $writeCallback, [null], $sizeCallback);
        $arguments     = [
            $extendeeQuoted,
            $nameQuoted,
            $tag,
            '$readCallback',
            '$writeCallback',
            '$sizeCallback'
        ];

        $body[] = 'if (self::$' . $name . ' !== null) {';
        $body[] = '    return self::$' . $name . ';';
        $body[] = '}';
        $body[] = null;
        $body   = array_merge($body, $callbacks);
        $body[] = null;
        $body[] = 'return self::$' . $name . ' = new \Protobuf\Extension(' . implode(', ', $arguments) . ');';

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

        $body[] = '$readCallback = function (\Protobuf\ReadContext $context, $wire) {';
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

        $body[] = '$writeCallback = function (\Protobuf\WriteContext $context, $value) {';
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
    protected function generateSizeCallback(FieldDescriptorProto $field)
    {
        $body  = [];
        $sttm  = $this->generateFieldSizeStatement($field);
        $lines = $this->addIndentation($sttm, 1);

        $body[] = '$sizeCallback = function (\Protobuf\ComputeSizeContext $context, $value) {';
        $body[] = '    $calculator = $context->getSizeCalculator();';
        $body[] = '    $size       = 0;';
        $body[] = null;
        $body   = array_merge($body, $lines);
        $body[] = null;
        $body[] = '    return $size;';
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

    /**
     * @param \google\protobuf\FieldDescriptorProto $field
     *
     * @return string[]
     */
    protected function generateFieldSizeStatement(FieldDescriptorProto $field)
    {
        $generator = new SerializedSizeFieldStatementGenerator($this->proto, $this->options, $this->package);

        $generator->setTargetVar('$value');

        return $generator->generateFieldSizeStatement($field);
    }
}
