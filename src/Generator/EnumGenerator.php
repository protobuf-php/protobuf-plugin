<?php

namespace Protobuf\Compiler\Generator;

use Protobuf\Compiler\Options;
use google\protobuf\EnumValueDescriptorProto;

use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\PropertyGenerator;

/**
 * Enum Generator
 *
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class EnumGenerator extends BaseGenerator
{
    /**
     * @return string
     */
    public function generate()
    {
        $name             = $this->proto->getName();
        $namespace        = trim($this->getNamespace($this->package), '\\');
        $className        = $this->getNamespace($this->package . '\\' . $name);
        $shortDescription = 'Protobuf enum : ' . $this->proto->getName();
        $class            = ClassGenerator::fromArray([
            'name'          => $name,
            'namespacename' => $namespace,
            'extendedclass' => '\Protobuf\Enum',
            'methods'       => $this->generateMethods($className),
            'properties'    => $this->generateProperties($className),
            'docblock'      => [
                'shortDescription' => $shortDescription,
            ]
        ]);

        return $this->generateFileContent($class);
    }

    /**
     * @param string $class
     *
     * @return string[]
     */
    public function generateProperties($class)
    {
        $properties = [];
        $constants  = $this->generateConstants($class);
        $values     = $this->proto->getValueList() ?: [];

        foreach ($values as $value) {
            $properties[] = PropertyGenerator::fromArray([
                'static'       => true,
                'name'         => $value->getName(),
                'visibility'   => PropertyGenerator::VISIBILITY_PROTECTED,
                'docblock'     => [
                    'tags'     => [
                        [
                            'name'        => 'var',
                            'description' => $class,
                        ]
                    ]
                ]
            ]);
        }

        return array_merge($constants, $properties);
    }

    /**
     * @param string $class
     *
     * @return string[]
     */
    public function generateConstants($class)
    {
        $constants = [];
        $values    = $this->proto->getValueList() ?: [];

        foreach ($values as $value) {
            $name     = $value->getName();
            $number   = $value->getNumber();
            $constant = PropertyGenerator::fromArray([
                'const'        => true,
                'defaultvalue' => $number,
                'name'         => $name . '_VALUE',
                'docblock'     => [
                    'shortDescription' => "$name = $number",
                ]
            ]);

            $constants[] = $constant;
        }

        return $constants;
    }

    /**
     * @param string $class
     *
     * @return string[]
     */
    public function generateMethods($class)
    {
        $methods = [];
        $values  = $this->proto->getValueList() ?: [];

        foreach ($values as $value) {
            $methods[] = $this->generateMethod($class, $value);
        }

        $methods[] = $this->generateValueOfMethod($class);

        return $methods;
    }

    /**
     * @param string                   $class
     * @param EnumValueDescriptorProto $value
     *
     * @return string
     */
    public function generateMethod($class, EnumValueDescriptorProto $value)
    {
        $body   = [];
        $name   = $value->getName();
        $number = $value->getNumber();
        $args   = var_export($name, true) . ', self::' . $name . '_VALUE';

        $body[] = 'if (self::$' . $name . ' !== null) {';
        $body[] = '    return self::$' . $name . ';';
        $body[] = '}';
        $body[] = null;
        $body[] = 'return self::$' . $name . ' = new ' . $class . '(' . $args . ');';

        return MethodGenerator::fromArray([
            'static'     => true,
            'name'       => $name,
            'body'       => implode(PHP_EOL, $body),
            'docblock'   => [
                'tags'     => [
                    [
                        'name'        => 'return',
                        'description' => $class,
                    ]
                ]
            ]
        ]);
    }

    /**
     * @param string $class
     *
     * @return string
     */
    public function generateValueOfMethod($class)
    {
        $body   = [];
        $values = $this->proto->getValueList() ?: [];

        $body[] = 'switch ($value) {';

        foreach ($values as $value) {
            $name   = $value->getName();
            $number = $value->getNumber();

            $body[] = '    case ' . $number . ': return self::' . $name . '();';
        }

        $body[] = '    default: return null;';
        $body[] = '}';

        return MethodGenerator::fromArray([
            'static'     => true,
            'name'       => 'valueOf',
            'body'       => implode(PHP_EOL, $body),
            'parameters' => [
                [
                    'name' => 'value',
                    'type' => 'int',
                ]
            ],
            'docblock'   => [
                'tags'     => [
                    [
                        'name'        => 'param',
                        'description' => 'int $value',
                    ],
                    [
                        'name'        => 'return',
                        'description' => $class,
                    ]
                ]
            ]
        ]);
    }
}
