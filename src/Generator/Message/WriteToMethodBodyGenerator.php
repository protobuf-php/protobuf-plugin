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
 * Message writeTo Body Generator
 *
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class WriteToMethodBodyGenerator extends BaseGenerator
{
    /**
     * @return string[]
     */
    public function generateBody()
    {
        $body[] = '$stream      = $context->getStream();';
        $body[] = '$writer      = $context->getWriter();';
        $body[] = '$sizeContext = $context->getComputeSizeContext();';
        $body[] = null;

        foreach (($this->proto->getFieldList() ?: []) as $field) {
            $lines = $this->generateRequiredFieldException($field);
            $body  = array_merge($body, $lines);
        }

        foreach (($this->proto->getFieldList() ?: []) as $field) {
            $lines = $this->generateFieldCondition($field);
            $body  = array_merge($body, $lines, [null]);
        }

        $body[] = 'return $stream;';

        return $body;
    }

    /**
     * @param \google\protobuf\FieldDescriptorProto $field
     *
     * @return string[]
     */
    public function generateRequiredFieldException(FieldDescriptorProto $field)
    {
        $name       = $field->getName();
        $label      = $field->getLabel();
        $tag        = $field->getNumber();
        $isRequired = $label === Label::LABEL_REQUIRED();

        if ( ! $isRequired) {
            return [];
        }

        $class   = $this->getNamespace($this->package . '.' . $this->proto->getName());
        $format  = 'Field "%s#%s" (tag %s) is required but has no value.';
        $message = var_export(sprintf($format, $class, $name, $tag), true);

        $body[] = 'if ($this->' . $name . ' === null) {';
        $body[] = '    throw new \UnexpectedValueException(' . $message . ');';
        $body[] = '}';
        $body[] = null;

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
        $sttm      = $this->generateFieldWriteStatement($field);
        $lines     = $this->addIndentation($sttm, 1);

        $body[] = sprintf($format, $fieldName);
        $body   = array_merge($body, $lines);
        $body[] = '}';

        return $body;
    }

    /**
     * @param google\protobuf\FieldDescriptorProto $field
     *
     * @return string[]
     */
    public function generateFieldWriteStatement(FieldDescriptorProto $field)
    {
        $generator = new WriteFieldStatementGenerator($this->proto, $this->options, $this->package);
        $statement = $generator->generateFieldWriteStatement($field);

        return $statement;
    }
}
