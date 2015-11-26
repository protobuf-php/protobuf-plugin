<?php

namespace ProtobufCompilerTest;

use google\protobuf\FieldDescriptorProto;

abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @param object $object
     * @param string $method
     * @param array  $args
     *
     * @return mixed
     */
    protected function invokeMethod($object, $method, array $args = [])
    {
        $reflection = new \ReflectionMethod($object, $method);

        $reflection->setAccessible(true);

        return $reflection->invokeArgs($object, $args);
    }

    /**
     * @param object $object
     * @param string $property
     *
     * @return mixed
     */
    protected function getPropertyValue($object, $property)
    {
        $reflection = new \ReflectionProperty($object, $property);

        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }

    /**
     * @param object $object
     * @param string $property
     * @param mixed  $value
     */
    protected function setPropertyValue($object, $property, $value)
    {
        $reflection = new \ReflectionProperty($object, $property);

        $reflection->setAccessible(true);
        $reflection->setValue($object, $value);
    }

    /**
     * @param string $name
     *
     * @return string
     */
    protected function getFixtureFileContent($name)
    {
       return file_get_contents(__DIR__ . '/Fixtures/' . $name);
    }

    /**
     * @param integer $number
     * @param string  $name
     * @param integer $type
     * @param integer $label
     * @param string  $typeName
     *
     * @return \google\protobuf\FieldDescriptorProto
     */
    protected function createFieldDescriptorProto($number, $name, $type, $label, $typeName = null)
    {
        $field = new FieldDescriptorProto();

        $field->setName($name);
        $field->setNumber($number);
        $field->setTypeName($typeName);
        $field->setType(FieldDescriptorProto\Type::valueOf($type));
        $field->setLabel(FieldDescriptorProto\Label::valueOf($label));

        return $field;
    }
}