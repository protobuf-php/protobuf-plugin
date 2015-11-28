<?php

namespace Protobuf\Compiler\Generator;

use Protobuf\Compiler\Entity;

use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\PropertyGenerator;

use google\protobuf\EnumValueDescriptorProto;

/**
 * Enum Generator
 *
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class EnumGenerator extends BaseGenerator
{
    /**
     * @param \Protobuf\Compiler\Entity $entity
     */
    public function visit(Entity $entity)
    {
        $name             = $entity->getName();
        $namespace        = $entity->getNamespace();
        $shortDescription = 'Protobuf enum : ' . $entity->getClass();
        $class            = ClassGenerator::fromArray([
            'name'          => $name,
            'namespacename' => $namespace,
            'extendedclass' => '\Protobuf\Enum',
            'methods'       => $this->generateMethods($entity),
            'properties'    => $this->generateProperties($entity),
            'docblock'      => [
                'shortDescription' => $shortDescription,
            ]
        ]);

        $entity->setContent($this->generateFileContent($class, $entity));
    }

    /**
     * @param \Protobuf\Compiler\Entity $entity
     *
     * @return string[]
     */
    protected function generateProperties(Entity $entity)
    {
        $properties = [];
        $descriptor = $entity->getDescriptor();
        $class      = $entity->getNamespacedName();
        $constants  = $this->generateConstants($entity);
        $values     = $descriptor->getValueList() ?: [];

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
     * @param \Protobuf\Compiler\Entity $entity
     *
     * @return string[]
     */
    public function generateConstants(Entity $entity)
    {
        $constants  = [];
        $descriptor = $entity->getDescriptor();
        $values     = $descriptor->getValueList() ?: [];

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
     * @param \Protobuf\Compiler\Entity $entity
     *
     * @return string[]
     */
    public function generateMethods(Entity $entity)
    {
        $methods    = [];
        $descriptor = $entity->getDescriptor();
        $values     = $descriptor->getValueList() ?: [];

        foreach ($values as $value) {
            $methods[] = $this->generateMethod($entity, $value);
        }

        $methods[] = $this->generateValueOfMethod($entity);

        return $methods;
    }

    /**
     * @param \Protobuf\Compiler\Entity $entity
     * @param EnumValueDescriptorProto  $value
     *
     * @return string
     */
    public function generateMethod(Entity $entity, EnumValueDescriptorProto $value)
    {
        $body   = [];
        $name   = $value->getName();
        $number = $value->getNumber();
        $class  = $entity->getNamespacedName();
        $args   = var_export($name, true) . ', self::' . $name . '_VALUE';

        $body[] = 'if (self::$' . $name . ' !== null) {';
        $body[] = '    return self::$' . $name . ';';
        $body[] = '}';
        $body[] = null;
        $body[] = 'return self::$' . $name . ' = new self(' . $args . ');';

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
     * @param \Protobuf\Compiler\Entity $entity
     *
     * @return string
     */
    public function generateValueOfMethod(Entity $entity)
    {
        $body        = [];
        $descriptor  = $entity->getDescriptor();
        $class       = $entity->getNamespacedName();
        $values      = $descriptor->getValueList() ?: [];

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
