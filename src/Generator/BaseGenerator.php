<?php

namespace Protobuf\Compiler\Generator;

use Protobuf\Field;
use Protobuf\Message;
use Protobuf\Configuration;
use Protobuf\Compiler\Options;
use Protobuf\Binary\SizeCalculator;

use google\protobuf\DescriptorProto;
use google\protobuf\FieldDescriptorProto;
use google\protobuf\FieldDescriptorProto\Type;
use google\protobuf\FieldDescriptorProto\Label;

use Doctrine\Common\Inflector\Inflector;

use Zend\Code\Generator\FileGenerator;
use Zend\Code\Generator\DocBlockGenerator;

/**
 * Base Generator
 *
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class BaseGenerator
{

    /**
     * @var \Protobuf\Binary\SizeCalculator
     */
    protected $calculator;

    /**
     * @var \Protobuf\Compiler\Options
     */
    protected $options;

    /**
     * @var \Protobuf\Message
     */
    protected $proto;

    /**
     * @var string
     */
    protected $package;

    /**
     * @var array
     */
    protected $scalarTypes = [
        'string' => true,
        'float'  => true,
        'bool'   => true,
        'int'    => true
    ];

    /**
     * @param \Protobuf\Message          $proto
     * @param \Protobuf\Compiler\Options $options
     * @param string                     $package
     */
    public function __construct(Message $proto, Options $options, $package)
    {
        $this->proto   = $proto;
        $this->options = $options;
        $this->package = $package;
    }

    /**
     * @param \google\protobuf\FieldDescriptorProto $field
     *
     * @return string
     */
    protected function getDoctype(FieldDescriptorProto $field)
    {
        $fieldType = $field->getType()->value();
        $phpType   = Field::getPhpType($fieldType);
        $refTypes  = [
            Type::TYPE_ENUM_VALUE    => true,
            Type::TYPE_MESSAGE_VALUE => true
        ];

        if (isset($refTypes[$fieldType])) {
            return $this->getNamespace($field->getTypeName());
        }

        return $phpType ?: 'mixed';
    }

    /**
     * @param \google\protobuf\FieldDescriptorProto $field
     *
     * @return string
     */
    protected function getDocBlockType(FieldDescriptorProto $field)
    {
        $type     = $this->getDoctype($field);
        $typeName = $field->getTypeName();
        $label    = $field->getLabel();

        if ($label === Label::LABEL_REPEATED()) {
            $type = '\Doctrine\Common\Collections\Collection';
        }

        if ($label === Label::LABEL_REPEATED() && $typeName !== null) {
            $reference = $this->getNamespace($typeName);
            $type      = $type . sprintf('<"%s">', $reference);
        }

        return $type;
    }

    /**
     * @param \google\protobuf\FieldDescriptorProto $field
     *
     * @return string
     */
    protected function getTypeHint(FieldDescriptorProto $field)
    {
        $type  = $this->getDoctype($field);
        $label = $field->getLabel();

        if ($label === Label::LABEL_REPEATED()) {
            return '\Doctrine\Common\Collections\Collection';
        }

        if (isset($this->scalarTypes[$type])) {
            return null;
        }

        return $type;
    }

    /**
     * Obtain the rule for the given field (repeated, optional, required).
     *
     * @param \google\protobuf\FieldDescriptorProto $field
     *
     * @return string
     */
    protected function getFieldLabelName(FieldDescriptorProto $field)
    {
        $label = $field->getLabel()->value();
        $name  = Field::getLabelName($label);

        return $name ?: 'unknown';
    }

    /**
     * @param \google\protobuf\FieldDescriptorProto $field
     *
     * @return string
     */
    protected function getFieldTypeName(FieldDescriptorProto $field)
    {
        $type = $field->getType()->value();
        $name = Field::getTypeName($type);

        return $name ?: 'unknown';
    }

    /**
     * @param \google\protobuf\FieldDescriptorProto $field
     *
     * @return string
     */
    protected function getClassifiedName(FieldDescriptorProto $field)
    {
        return Inflector::classify($field->getName());
    }

    /**
     * @param string                                $type
     * @param \google\protobuf\FieldDescriptorProto $field
     *
     * @return string
     */
    protected function getAccessorName($type, FieldDescriptorProto $field)
    {
        $classified = $this->getClassifiedName($field);
        $method     = $type . $classified;

        if ($field->getLabel() === Label::LABEL_REPEATED()) {
            return $method . 'List';
        }

        return $method;
    }

    /**
     * Convert a Protobuf package to a php namespace
     *
     * @param string $namespace
     *
     * @return string
     */
    protected function getNamespace($namespace)
    {
        $name = str_replace('.', '\\', $namespace);
        $fqcn = '\\' . trim($name, '\\');

        return $fqcn;
    }

    /**
     * @param \google\protobuf\FieldDescriptorProto $field
     *
     * @return string
     */
    protected function getDefaultFieldValue(FieldDescriptorProto $field)
    {
        $type  = $field->getType();
        $value = $field->getDefaultValue();

        if ($type === Type::TYPE_ENUM()) {
            $ref   = $field->getTypeName();
            $const = $this->getNamespace($ref) . '::' . $value . '()';

            return $const;
        }

        if ($type === Type::TYPE_BOOL()) {
            $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        return var_export($value, true);
    }

    /**
     * @param string  $class
     *
     * @return string
     */
    protected function generateFileContent($class)
    {
        $file = new FileGenerator();

        $file->setClass($class);
        $file->setDocblock(DocBlockGenerator::fromArray(array(
            'shortDescription' => 'Generated by Protobuf protoc plugin.'
        )));

        return $file->generate();
    }

    /**
     * @param \google\protobuf\DescriptorProto $descriptor
     * @param string                           $default
     *
     * @return string
     */
    protected function getUniqueFieldName(DescriptorProto $descriptor, $default)
    {
        $extensions = $descriptor->getExtensionList() ?: [];
        $fields     = $descriptor->getFieldList() ?: [];
        $name       = $default;
        $names      = [];
        $count      = 0;

        foreach ($fields as $field) {
            $names[$field->getName()] = true;
        }

        foreach ($extensions as $field) {
            $names[$field->getName()] = true;
        }

        while (isset($names[$name])) {
            $name = $default . ($count ++);
        }

        return $name;
    }

    /**
     * @param array   $body
     * @param integer $level
     * @param string  $indentation
     *
     * @return string[]
     */
    protected function addIndentation(array $body, $level, $indentation = '    ')
    {
        $identation = str_repeat($indentation, $level);
        $lines      = array_map(function ($line) use ($identation) {
            return $line !== null
                ? $identation . $line
                : $line;
        }, $body);

        return $lines;
    }

    /**
     * compute value size
     *
     * @param string  $type
     *
     * @return string
     */
    public function getComputeSizeMetadata($type)
    {
        $data = [
            'method' => null,
            'size'   => null
        ];

        $dynamicMapping = [
            Type::TYPE_INT32_VALUE  => 'computeVarintSize',
            Type::TYPE_INT64_VALUE  => 'computeVarintSize',
            Type::TYPE_UINT64_VALUE => 'computeVarintSize',
            Type::TYPE_UINT32_VALUE => 'computeVarintSize',
            Type::TYPE_ENUM_VALUE   => 'computeVarintSize',
            Type::TYPE_STRING_VALUE => 'computeStringSize',
            Type::TYPE_BYTES_VALUE  => 'computeStringSize',
            Type::TYPE_SINT32_VALUE => 'computeZigzag32Size',
            Type::TYPE_SINT64_VALUE => 'computeZigzag64Size',
        ];

        if (isset($dynamicMapping[$type])) {
            $data['method'] = $dynamicMapping[$type];

            return $data;
        }

        if ($type === Type::TYPE_DOUBLE_VALUE) {
            $data['method'] = 'computeDoubleSize';
            $data['size']   = $this->getSizeCalculator()->computeDoubleSize();

            return $data;
        }

        if ($type === Type::TYPE_FLOAT_VALUE) {
            $data['method'] = 'computeFloatSize';
            $data['size']   = $this->getSizeCalculator()->computeFloatSize();

            return $data;
        }

        if ($type === Type::TYPE_BOOL_VALUE) {
            $data['method'] = 'computeBoolSize';
            $data['size']   = $this->getSizeCalculator()->computeBoolSize();

            return $data;
        }

        if ($type === Type::TYPE_FIXED64_VALUE) {
            $data['method'] = 'computeFixed64Size';
            $data['size']   = $this->getSizeCalculator()->computeFixed64Size();

            return $data;
        }

        if ($type === Type::TYPE_SFIXED64_VALUE) {
            $data['method'] = 'computeSFixed64Size';
            $data['size']   = $this->getSizeCalculator()->computeSFixed64Size();

            return $data;
        }

        if ($type === Type::TYPE_FIXED32_VALUE) {
            $data['method'] = 'computeFixed32Size';
            $data['size']   = $this->getSizeCalculator()->computeFixed32Size();

            return $data;
        }

        if ($type === Type::TYPE_SFIXED32_VALUE) {
            $data['method'] = 'computeSFixed32Size';
            $data['size']   = $this->getSizeCalculator()->computeSFixed32Size();

            return $data;
        }

        throw new \Exception('Unknown field type ' . $type);
    }

    /**
     * @return string
     */
    public function getSizeCalculator()
    {
        if ($this->calculator !== null) {
            return $this->calculator;
        }

        $config     = Configuration::getInstance();
        $calculator = new SizeCalculator($config);

        return $this->calculator = $calculator;
    }

    /**
     * Compute value size
     *
     * @param integer $type
     * @param string  $value
     *
     * @return string
     */
    public function generateValueSizeStatement($type, $value)
    {
        $metadata = $this->getComputeSizeMetadata($type);
        $method   = $metadata['method'];
        $size     = $metadata['size'];

        if ($size !== null) {
            return $size;
        }

        return sprintf('$calculator->%s(%s)', $method, $value);
    }
}
