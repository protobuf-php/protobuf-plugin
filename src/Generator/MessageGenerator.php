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

use Protobuf\Compiler\Generator\Message\AnnotationGenerator;
use Protobuf\Compiler\Generator\Message\ExtensionMethodBodyGenerator;
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
        $longDescription  = $this->generateMessageAnnotation();
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
     * @return string
     */
    protected function generateMessageAnnotation()
    {
        $generator  = new AnnotationGenerator($this->proto, $this->options, $this->package);
        $annotation = implode(PHP_EOL, $generator->generateAnnotation());

        return $annotation;
    }

    /**
     * @return string[]
     */
    public function generateFields()
    {
        $unknown = PropertyGenerator::fromArray([
            'name'       => $this->getUniqueFieldName($this->proto, 'unknownFieldSet'),
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

        $extensions = PropertyGenerator::fromArray([
            'name'       => $this->getUniqueFieldName($this->proto, 'extensions'),
            'visibility' => PropertyGenerator::VISIBILITY_PROTECTED,
            'docblock'   => [
                'tags'   => [
                    [
                        'name'        => 'var',
                        'description' => '\Protobuf\ExtensionFieldMap',
                    ]
                ]
            ]
        ]);

        $fields = [];

        foreach (($this->proto->getExtensionList() ?: []) as $field) {
            $fields[] = $this->generateExtensionField($field);
        }

        $fields[] = $unknown;
        $fields[] = $extensions;

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
     * @param \google\protobuf\FieldDescriptorProto $field
     *
     * @return string
     */
    public function generateExtensionField(FieldDescriptorProto $field)
    {
        $name     = $field->getName();
        $number   = $field->getNumber();
        $docBlock = $this->getDocBlockType($field);
        $type     = $this->getFieldTypeName($field);
        $label    = $this->getFieldLabelName($field);
        $property = PropertyGenerator::fromArray([
            'static'       => true,
            'name'         => $name,
            'visibility'   => PropertyGenerator::VISIBILITY_PROTECTED,
            'docblock'     => [
                'shortDescription' => "Extension field : $name $label $type = $number",
                'tags'             => [
                    [
                        'name'        => 'var',
                        'description' => '\Protobuf\Extension',
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
    public function generateMethods()
    {
        $extensions  = $this->generateExtensionMethods();
        $constructor = $this->generateConstructorMethod();
        $accessors   = $this->generateGetterAndSetterMethods();
        $methods     = [];

        if ($constructor) {
            $methods[] = $constructor;
        }

        $methods = array_merge($methods, $extensions, $accessors);

        $methods[] = $this->generateUnknownFieldSetMethod();
        $methods[] = $this->generateExtensionsMethod();
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
    public function generateExtensionMethods()
    {
        $methods = [];

        foreach (($this->proto->getExtensionList() ?: []) as $field) {
            $methods[] = $this->generateExtensionMethod($field);
        }

        return $methods;
    }

    /**
     * @param \google\protobuf\FieldDescriptorProto $field
     *
     * @return string
     */
    public function generateExtensionMethod(FieldDescriptorProto $field)
    {
        $fieldName  = $field->getName();
        $bodyGen    = new ExtensionMethodBodyGenerator($this->proto, $this->options, $this->package);
        $body       = implode(PHP_EOL, $bodyGen->generateBody($field));
        $method     = MethodGenerator::fromArray([
            'static'     => true,
            'body'       => $body,
            'name'       => $fieldName,
            'docblock'   => [
                'shortDescription' => "Extension field : $fieldName",
                'tags'             => [
                    [
                        'name'        => 'return',
                        'description' => '\Protobuf\Extension',
                    ]
                ]
            ]
        ]);

        $method->getDocblock()->setWordWrap(false);

        return $method;
    }

    /**
     * @return string
     */
    public function generateUnknownFieldSetMethod()
    {
        $methodName = 'unknownFieldSet';
        $fieldName  = $this->getUniqueFieldName($this->proto, $methodName);
        $method     = MethodGenerator::fromArray([
            'name'       => $methodName,
            'body'       => 'return $this->' . $fieldName . ';',
            'docblock'   => [
                'shortDescription' => "Get unknown values",
                'tags'             => [
                    [
                        'name'        => 'return',
                        'description' => '\Protobuf\UnknownFieldSet',
                    ]
                ]
            ]
        ]);

        return $method;
    }

    /**
     * @return string
     */
    public function generateExtensionsMethod()
    {
        $lines      = [];
        $fieldName  = $this->getUniqueFieldName($this->proto, 'extensions');

        $lines[] = 'if ( $this->' . $fieldName . ' !== null) {';
        $lines[] = '    return $this->' . $fieldName . ';';
        $lines[] = '}';
        $lines[] = null;
        $lines[] = 'return $this->' . $fieldName . ' = new \Protobuf\ExtensionFieldMap(__CLASS__);';

        return MethodGenerator::fromArray([
            'name'       => 'extensions',
            'body'       => implode(PHP_EOL, $lines),
            'docblock'   => [
                'shortDescription' => "{@inheritdoc}",
                'tags'             => [
                    [
                        'name'        => 'return',
                        'description' => '\Protobuf\ExtensionFieldMap',
                    ]
                ]
            ]
        ]);
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
