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
     * @return string
     */
    public function generate()
    {
        $extendee         = $this->proto->getExtendee();
        $namespace        = trim($this->getNamespace($extendee), '\\');
        $name             = Inflector::classify($this->proto->getName());
        $shortDescription = 'Protobuf exntension : ' . $this->proto->getName();
        $class            = ClassGenerator::fromArray([
            'name'                  => $name,
            'namespacename'         => $namespace,
            'implementedinterfaces' => ['\Protobuf\Extension'],
            'properties'            => $this->generateFields(),
            'methods'               => $this->generateMethods(),
            'docblock'              => [
                'shortDescription' => $shortDescription,
                //'longDescription'  => $longDescription
            ]
        ]);

        return $this->generateFileContent($class);
    }

    /**
     * @return string[]
     */
    public function generateFields()
    {
        $extension = PropertyGenerator::fromArray([
            'static'     => true,
            'name'       => 'extension',
            'visibility' => PropertyGenerator::VISIBILITY_PROTECTED,
            'docblock'   => [
                'tags'   => [
                    [
                        'name'        => 'var',
                        'description' => '\Protobuf\Extension',
                    ]
                ]
            ]
        ]);

        return [$extension];
    }

    /**
     * @return string[]
     */
    public function generateMethods()
    {
        $methods   = [];
        $methods[] = $this->generateExtensionMethod();
        $methods[] = $this->generateGetExtendeeMethod();
        $methods[] = $this->generateGetNameMethod();
        $methods[] = $this->generateGetTagMethod();
        $methods[] = $this->generateReadFromMethod();
        $methods[] = $this->generateWriteToMethod();
        $methods[] = $this->generateSerializedSizeMethod();

        return $methods;
    }

    /**
     * @return string
     */
    protected function generateExtensionMethod()
    {
        $body[] = 'if (self::$extension !== null) {';
        $body[] = '    return self::$extension;';
        $body[] = '}';
        $body[] = null;
        $body[] = 'return self::$extension = new self();';

        return MethodGenerator::fromArray([
            'static'     => true,
            'body'       => implode(PHP_EOL, $body),
            'name'       => 'extension',
            'docblock'   => [
                'shortDescription' => '\Protobuf\Extension'
            ]
        ]);
    }

    /**
     * @return string
     */
    protected function generateGetExtendeeMethod()
    {
        $extendee   = $this->getNamespace($this->proto->getExtendee());
        $body       = sprintf('return %s;', var_export($extendee, true));
        $method     = MethodGenerator::fromArray([
            'body'       => $body,
            'name'       => 'getExtendee',
            'docblock'   => [
                'shortDescription' => '{@inheritdoc}'
            ]
        ]);

        return $method;
    }

    /**
     * @return string
     */
    protected function generateGetNameMethod()
    {
        $fieldName = $this->proto->getName();
        $body      = sprintf('return %s;', var_export($fieldName, true));
        $method    = MethodGenerator::fromArray([
            'body'       => $body,
            'name'       => 'getName',
            'docblock'   => [
                'shortDescription' => '{@inheritdoc}'
            ]
        ]);

        return $method;
    }

    /**
     * @return string
     */
    protected function generateGetTagMethod()
    {
        $fieldTag = $this->proto->getNumber();
        $body     = sprintf('return %s;', var_export($fieldTag, true));
        $method   = MethodGenerator::fromArray([
            'body'       => $body,
            'name'       => 'getTag',
            'docblock'   => [
                'shortDescription' => '{@inheritdoc}'
            ]
        ]);

        return $method;
    }

    /**
     * @return string
     */
    protected function generateReadFromMethod()
    {
        $fieldTag = $this->proto->getNumber();
        $body     = implode(PHP_EOL, $this->generateReadFromMethodBody());
        $method   = MethodGenerator::fromArray([
            'body'       => $body,
            'name'       => 'readFrom',
            'parameters' => [
                [
                    'name' => 'context',
                    'type' => '\Protobuf\ReadContext',
                ],
                [
                    'name' => 'wire',
                    'type' => 'int',
                ]
            ],
            'docblock'   => [
                'shortDescription' => '{@inheritdoc}'
            ]
        ]);

        return $method;
    }

    /**
     * @return string[]
     */
    protected function generateReadFromMethodBody()
    {
        $body  = [];
        $lines = $this->generateFieldReadStatement();

        $body[] = '$reader = $context->getReader();';
        $body[] = '$length = $context->getLength();';
        $body[] = '$stream = $context->getStream();';
        $body[] = null;

        return array_merge($body, $lines);
    }

    /**
     * @return string
     */
    protected function generateWriteToMethod()
    {
        $fieldTag = $this->proto->getNumber();
        $body     = implode(PHP_EOL, $this->generateWriteFromMethodBody());
        $method   = MethodGenerator::fromArray([
            'body'       => $body,
            'name'       => 'writeTo',
            'parameters' => [
                [
                    'name' => 'context',
                    'type' => '\Protobuf\WriteContext',
                ],
                [
                    'name' => 'value',
                    'type' => 'mixed',
                ]
            ],
            'docblock'   => [
                'shortDescription' => '{@inheritdoc}'
            ]
        ]);

        return $method;
    }

    /**
     * @return string[]
     */
    protected function generateWriteFromMethodBody()
    {
        $body  = [];
        $lines = $this->generateFieldWriteStatement($this->proto);

        $body[] = '$stream      = $context->getStream();';
        $body[] = '$writer      = $context->getWriter();';
        $body[] = '$sizeContext = $context->getComputeSizeContext();';
        $body[] = null;

        return array_merge($body, $lines);
    }

    /**
     * @return string
     */
    protected function generateSerializedSizeMethod()
    {
        $fieldTag = $this->proto->getNumber();
        $body     = implode(PHP_EOL, $this->generateSerializedSizeMethodBody());
        $method   = MethodGenerator::fromArray([
            'body'       => $body,
            'name'       => 'serializedSize',
            'parameters' => [
                [
                    'name' => 'context',
                    'type' => '\Protobuf\ComputeSizeContext',
                ],
                [
                    'name' => 'value',
                    'type' => 'mixed',
                ]
            ],
            'docblock'   => [
                'shortDescription' => '{@inheritdoc}'
            ]
        ]);

        return $method;
    }

    /**
     * @return string[]
     */
    protected function generateSerializedSizeMethodBody()
    {
        $body  = [];
        $lines = $this->generateFieldSizeStatement($this->proto);

        $body[] = '$calculator = $context->getSizeCalculator();';
        $body[] = '$size       = 0;';
        $body[] = null;
        $body   = array_merge($body, $lines);
        $body[] = null;
        $body[] = 'return $size;';

        return $body;
    }

    /**
     * @return string[]
     */
    protected function generateFieldReadStatement()
    {
        $generator = new ReadFieldStatementGenerator($this->proto, $this->options, $this->package);

        $generator->setBreakMode(ReadFieldStatementGenerator::BREAK_MODE_RETURN);
        $generator->setTargetVar('$value');

        return $generator->generateFieldReadStatement($this->proto);
    }

    /**
     * @return string[]
     */
    protected function generateFieldWriteStatement()
    {
        $generator = new WriteFieldStatementGenerator($this->proto, $this->options, $this->package);

        $generator->setTargetVar('$value');

        return $generator->generateFieldWriteStatement($this->proto);
    }

    /**
     * @return string[]
     */
    protected function generateFieldSizeStatement()
    {
        $generator = new SerializedSizeFieldStatementGenerator($this->proto, $this->options, $this->package);

        $generator->setTargetVar('$value');

        return $generator->generateFieldSizeStatement($this->proto);
    }
}
