<?php

namespace Protobuf\Compiler\Generator;

use Protobuf\WireFormat;
use Protobuf\Compiler\Options;

use google\protobuf\DescriptorProto;
use google\protobuf\FieldDescriptorProto;
use google\protobuf\FieldDescriptorProto\Type;
use google\protobuf\FieldDescriptorProto\Label;

use Doctrine\Common\Inflector\Inflector;

use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\PropertyGenerator;
use Zend\Code\Generator\ParameterGenerator;

use Protobuf\Compiler\Generator\Message\WriteToMethodBodyGenerator;
use Protobuf\Compiler\Generator\Message\ReadFromMethodBodyGenerator;
use Protobuf\Compiler\Generator\Message\ToStreamMethodBodyGenerator;
use Protobuf\Compiler\Generator\Message\ConstructMethodBodyGenerator;
use Protobuf\Compiler\Generator\Message\FromStreamMethodBodyGenerator;
use Protobuf\Compiler\Generator\Message\SerializedSizeMethodBodyGenerator;

/**
 * Message Generator
 *
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class MessageGenerator extends BaseGenerator
{
    /**
     * @return string
     */
    public function generate()
    {
        $name             = $this->proto->getName();
        $namespace        = trim($this->getNamespace($this->package), '\\');
        $shortDescription = 'Protobuf message : ' . $this->proto->getName();
        $longDescription  = implode(PHP_EOL, $this->generateMessageAnnotation());
        $class            = ClassGenerator::fromArray([
            'name'          => $name,
            'namespacename' => $namespace,
            'extendedClass' => '\Protobuf\AbstractMessage',
            'properties'    => $this->generateFields(),
            'methods'       => $this->generateMethods(),
            'docblock'      => [
                'shortDescription' => $shortDescription,
                'longDescription'  => $longDescription
            ]
        ]);

        return $this->generateFileContent($class);
    }

    /**
     * @return string[]
     */
    public function generateFields()
    {
        $fields  = [];
        $unknown = PropertyGenerator::fromArray([
            'name'       => $this->getUnknownFieldSetName($this->proto),
            'visibility' => PropertyGenerator::VISIBILITY_PROTECTED,
            'docblock'   => [
                'tags'   => [
                    [
                        'name'        => 'var',
                        'description' => '\Protobuf\UnknownFieldSet',
                    ]
                ]
            ]
        ]);

        $fields[] = $unknown;

        foreach (($this->proto->getFieldList() ?: []) as $field) {
            $fields[] = $this->generateField($field);
        }

        return $fields;
    }

    /**
     * @param \google\protobuf\FieldDescriptorProto $field
     *
     * @return string
     */
    public function generateField(FieldDescriptorProto $field)
    {
        $name     = $field->getName();
        $number   = $field->getNumber();
        $docBlock = $this->getDocBlockType($field);
        $type     = $this->getFieldTypeName($field);
        $label    = $this->getFieldLabelName($field);
        $property = PropertyGenerator::fromArray([
            'name'         => $name,
            'visibility'   => PropertyGenerator::VISIBILITY_PROTECTED,
            'docblock'     => [
                'shortDescription' => "$name $label $type = $number",
                'tags'             => [
                    [
                        'name'        => 'var',
                        'description' => $docBlock,
                    ]
                ]
            ]
        ]);

        $property->getDocblock()->setWordWrap(false);

        return $property;
    }

    /**
     * @return string[]
     */
    public function generateMessageAnnotation()
    {
        $package = json_encode($this->package);
        $name    = json_encode($this->proto->getName());

        $lines[] = "@\Protobuf\Annotation\Descriptor(";
        $lines[] = "  name=$name,";
        $lines[] = "  package=$package,";
        $lines[] = "  fields={";

        foreach (($this->proto->getFieldList() ?: []) as $field) {
            $annot = $this->generateFieldAnnotation($field);
            $annot = $this->addIndentation($annot, 2, '  ');
            $lines = array_merge($lines, $annot);
        }

        $index = count($lines) -1;
        $value = $lines[$index];

        $lines[$index] = trim($value, ',');

        $lines[] = "  }";
        $lines[] = ")";

        return $lines;
    }

    /**
     * @param \google\protobuf\FieldDescriptorProto $field
     *
     * @return string[]
     */
    public function generateFieldAnnotation(FieldDescriptorProto $field)
    {
        $lines     = [];
        $type      = $field->getType();
        $name      = $field->getName();
        $label     = $field->getLabel();
        $number    = $field->getNumber();
        $options   = $field->getOptions();
        $reference = $field->getTypeName();
        $default   = $field->getDefaultValue();
        $isPack    = $options ? $options->getPacked() : false;

        $tags    = [];
        $mapping = [
            'name'   => $name,
            'tag'    => $number,
            'type'   => $type->value(),
            'label'  => $label->value(),
        ];

        if ($default) {
            $mapping['default'] = $default;
        }

        if ($isPack) {
            $mapping['pack'] = $isPack;
        }

        if ($reference) {
            $mapping['reference'] = trim($reference, '.');
        }

        foreach ($mapping as $key => $value) {
            $tags[] = "$key=" . json_encode($value) . ',';
        }

        $index = count($tags) -1;
        $value = $tags[$index];

        $tags[$index] = trim($value, ',');

        $lines   = ['@\Protobuf\Annotation\Field('];
        $lines   = array_merge($lines, $this->addIndentation($tags, 1, '  '));
        $lines[] = '),';

        return $lines;
    }

    /**
     * @return string[]
     */
    public function generateMethods()
    {
        $constructor = $this->generateConstructorMethod();
        $accessors   = $this->generateGetterAndSetterMethods();
        $methods     = [];

        if ($constructor) {
            $methods[] = $constructor;
        }

        $methods = array_merge($methods, $accessors);

        $methods[] = $this->generateUnknownFieldSetMethod();
        $methods[] = $this->generateSerializedSizeMethod();
        $methods[] = $this->generateToStreamMethod();
        $methods[] = $this->generateReadFromMethod();
        $methods[] = $this->generateWriteToMethod();
        $methods[] = $this->generateFromStreamMethod();

        return $methods;
    }

    /**
     * @return string
     */
    public function generateGetterAndSetterMethods()
    {
        $methods = [];

        foreach (($this->proto->getFieldList() ?: []) as $field) {
            $methods[] = $this->generateHasMethod($field);
            $methods[] = $this->generateGetterMethod($field);
            $methods[] = $this->generateSetterMethod($field);

            if ($field->getLabel() === Label::LABEL_REPEATED()) {
                $methods[] = $this->generateAddMethod($field);
            }
        }

        return $methods;
    }

    /**
     * @return string
     */
    public function generateUnknownFieldSetMethod()
    {
        $methodName = 'unknownFieldSet';
        $fieldName  = $this->getUnknownFieldSetName($this->proto);
        $method     = MethodGenerator::fromArray([
            'name'       => $methodName,
            'body'       => 'return $this->' . $fieldName . ';',
            'docblock'   => [
                'shortDescription' => "Get unknown values",
                'tags'             => [
                    [
                        'name'        => 'return',
                        'description' => 'Protobuf\UnknownFieldSet',
                    ]
                ]
            ]
        ]);

        return $method;
    }

    /**
     * @param \google\protobuf\FieldDescriptorProto $field
     *
     * @return string
     */
    public function generateGetterMethod(FieldDescriptorProto $field)
    {
        $fieldName  = $field->getName();
        $methodName = $this->getAccessorName('get', $field);
        $method     = MethodGenerator::fromArray([
            'name'       => $methodName,
            'body'       => 'return $this->' . $fieldName . ';',
            'docblock'   => [
                'shortDescription' => "Get '$fieldName' value",
                'tags'             => [
                    [
                        'name'        => 'return',
                        'description' => $this->getDocBlockType($field),
                    ]
                ]
            ]
        ]);

        $method->getDocblock()->setWordWrap(false);

        return $method;
    }

    /**
     * @param google\protobuf\FieldDescriptorProto $field
     *
     * @return string
     */
    public function generateSetterMethod(FieldDescriptorProto $field)
    {
        $fieldName  = $field->getName();
        $methodName = $this->getAccessorName('set', $field);
        $method     = MethodGenerator::fromArray([
            'name'       => $methodName,
            'body'       => 'return $this->' . $fieldName . ' = $value;',
            'parameters' => [
                [
                    'name'   => 'value',
                    'type'   => $this->getTypeHint($field)
                ]
            ],
            'docblock'   => [
                'shortDescription' => "Set '$fieldName' value",
                'tags'             => [
                    [
                        'name'        => 'param',
                        'description' => $this->getDocBlockType($field) . ' $value'
                    ]
                ]
            ]
        ]);

        $method->getDocblock()->setWordWrap(false);

        return $method;
    }

    /**
     * @param google\protobuf\FieldDescriptorProto $field
     *
     * @return string
     */
    public function generateHasMethod(FieldDescriptorProto $field)
    {
        $fieldName  = $field->getName();
        $methodName = $this->getAccessorName('has', $field);
        $method     = MethodGenerator::fromArray([
            'name'       => $methodName,
            'body'       => 'return $this->' . $fieldName . ' !== null;',
            'docblock'   => [
                'shortDescription' => "Check if '$fieldName' has a value",
                'tags'             => [
                    [
                        'name'        => 'return',
                        'description' => 'bool',
                    ]
                ]
            ]
        ]);

        return $method;
    }

    /**
     * @param google\protobuf\FieldDescriptorProto $field
     *
     * @return string
     */
    public function generateAddMethod(FieldDescriptorProto $field)
    {
        $fieldName  = $field->getName();
        $methodName = 'add' . $this->getClassifiedName($field);
        $method     = MethodGenerator::fromArray([
            'name'       => $methodName,
            'body'       => '$this->' . $fieldName . '[] = $value;',
            'parameters' => [
                [
                    'name'   => 'value',
                    'type'   => $this->getDoctype($field)
                ]
            ],
            'docblock'   => [
                'shortDescription' => "Add a new element to '$fieldName'",
                'tags'             => [
                    [
                        'name'        => 'param',
                        'description' => $this->getDoctype($field) . ' $value'
                    ]
                ]
            ]
        ]);

        return $method;
    }

    /**
     * @return string
     */
    public function generateWriteToMethod()
    {
        $bodyGen = new WriteToMethodBodyGenerator($this->proto, $this->options, $this->package);
        $body    = implode(PHP_EOL, $bodyGen->generateBody());
        $method  = MethodGenerator::fromArray([
            'name'       => 'writeTo',
            'body'       => $body,
            'parameters' => [
                [
                    'name' => 'context',
                    'type' => '\Protobuf\WriteContext',
                ]
            ],
            'docblock'   => [
                'shortDescription' => "{@inheritdoc}"
            ]
        ]);

        return $method;
    }

    /**
     * @return string
     */
    public function generateSerializedSizeMethod()
    {
        $bodyGen = new SerializedSizeMethodBodyGenerator($this->proto, $this->options, $this->package);
        $body    = implode(PHP_EOL, $bodyGen->generateBody());
        $method  = MethodGenerator::fromArray([
            'name'       => 'serializedSize',
            'body'       => $body,
            'parameters' => [
                [
                    'name'         => 'context',
                    'type'         => '\Protobuf\ComputeSizeContext'
                ]
            ],
            'docblock'   => [
                'shortDescription' => "{@inheritdoc}"
            ]
        ]);

        return $method;
    }

    /**
     * @return string
     */
    public function generateConstructorMethod()
    {
        $bodyGen = new ConstructMethodBodyGenerator($this->proto, $this->options, $this->package);
        $body    = implode(PHP_EOL, $bodyGen->generateBody());
        $method  = MethodGenerator::fromArray([
            'name'       => '__construct',
            'body'       => $body,
            'docblock'   => [
                'shortDescription' => "Constructor"
            ]
        ]);

        if (trim($body) == '') {
            return null;
        }

        return $method;
    }

    /**
     * @return string
     */
    public function generateReadFromMethod()
    {
        $bodyGen = new ReadFromMethodBodyGenerator($this->proto, $this->options, $this->package);
        $body    = implode(PHP_EOL, $bodyGen->generateBody());
        $method  = MethodGenerator::fromArray([
            'name'       => 'readFrom',
            'body'       => $body,
            'parameters' => [
                [
                    'name'          => 'context',
                    'type'          => '\Protobuf\ReadContext',
                ]
            ],
            'docblock'   => [
                'shortDescription' => "{@inheritdoc}"
            ]
        ]);

        return $method;
    }

    /**
     * @return string
     */
    public function generateFromStreamMethod()
    {
        $bodyGen = new FromStreamMethodBodyGenerator($this->proto, $this->options, $this->package);
        $body    = implode(PHP_EOL, $bodyGen->generateBody());
        $method  = MethodGenerator::fromArray([
            'name'       => 'fromStream',
            'body'       => $body,
            'static'     => true,
            'parameters' => [
                [
                    'name'          => 'stream',
                    'type'          => 'mixed',
                ],
                [
                    'name'          => 'configuration',
                    'type'          => '\Protobuf\Configuration',
                    'defaultValue'  => null
                ]
            ],
            'docblock'   => [
                'shortDescription' => "{@inheritdoc}"
            ]
        ]);

        return $method;
    }

    /**
     * @return string
     */
    public function generateToStreamMethod()
    {
        $bodyGen = new ToStreamMethodBodyGenerator($this->proto, $this->options, $this->package);
        $body    = implode(PHP_EOL, $bodyGen->generateBody());
        $method  = MethodGenerator::fromArray([
            'name'       => 'toStream',
            'body'       => $body,
            'parameters' => [
                [
                    'name'          => 'configuration',
                    'type'          => '\Protobuf\Configuration',
                    'defaultValue'  => null
                ]
            ],
            'docblock'   => [
                'shortDescription' => "{@inheritdoc}"
            ]
        ]);

        return $method;
    }
}
