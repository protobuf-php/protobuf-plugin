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

use Protobuf\Compiler\Generator\Message\ReadFieldStatementGenerator;
use Protobuf\Compiler\Generator\Message\ExtensionMethodBodyGenerator;
use Protobuf\Compiler\Generator\Message\WriteFieldStatementGenerator;
use Protobuf\Compiler\Generator\Message\SerializedSizeFieldStatementGenerator;

/**
 * Extension Generator
 *
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class ExtensionGenerator extends BaseGenerator
{
    /**
     * @param string $namespace
     *
     * @return string
     */
    public function generate($namespace)
    {
        $namespace        = trim($this->getNamespace($namespace), '\\');
        $shortDescription = 'Protobuf extension : ' . $namespace;
        $class            = ClassGenerator::fromArray([
            'name'                  => 'Extension',
            'namespacename'         => $namespace,
            'implementedinterfaces' => ['\Protobuf\Extension'],
            'properties'            => $this->generateFields(),
            'methods'               => $this->generateMethods(),
            'docblock'              => [
                'shortDescription' => $shortDescription
            ]
        ]);

        return $this->generateFileContent($class);
    }

    /**
     * @return string[]
     */
    public function generateFields()
    {
        $fields = [];

        foreach (($this->proto->getExtensionList() ?: []) as $field) {
            $fields[] = $this->generateExtensionField($field);
        }

        return $fields;
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
        $extensions = $this->proto->getExtensionList() ?: [];
        $methods    = [$this->generateRegisterAllExtensionsMethod()];

        foreach ($extensions as $field) {
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
    public function generateRegisterAllExtensionsMethod()
    {
        $lines      = [];
        $fields     = [];
        $extensions = $this->proto->getExtensionList() ?: [];
        $messages   = $this->proto->getMessageTypeList() ?: [];

        foreach ($messages as $message) {

            if ( ! $message->hasExtensionList()) {
                continue;
            }

            foreach ($message->getExtensionList() as $extension) {
                $fields[] = $extension;
            }
        }

        foreach ($extensions as $field) {
            $fields[] = $field;
        }

        foreach ($fields as $field) {
            $name  = $field->getName();
            $type  = $field->getTypeName();
            $class = $type ? $this->getNamespace($type) : 'self';
            $sttm  = '$registry->add(' . $class . '::' . $name. '());';

            $lines[] = $sttm;
        }

        $body       = implode(PHP_EOL, $lines);
        $method     = MethodGenerator::fromArray([
            'static'     => true,
            'body'       => $body,
            'name'       => 'registerAllExtensions',
            'parameters' => [
                [
                    'name' => 'registry',
                    'type' => '\Protobuf\Extension\ExtensionRegistry'
                ]
            ],
            'docblock'   => [
                'shortDescription' => "Register all extensions",
                'tags'             => [
                    [
                        'name'        => 'param',
                        'description' => '\Protobuf\Extension\ExtensionRegistry',
                    ]
                ]
            ]
        ]);

        return $method;
    }
}
