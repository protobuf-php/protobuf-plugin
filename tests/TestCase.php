<?php

namespace ProtobufCompilerTest;

use Protobuf\Configuration;
use Protobuf\Compiler\Options;
use Protobuf\Compiler\Context;
use Protobuf\Compiler\EntityBuilder;


use google\protobuf\FileOptions;
use google\protobuf\FieldOptions;
use google\protobuf\DescriptorProto;
use google\protobuf\EnumDescriptorProto;
use google\protobuf\FileDescriptorProto;
use google\protobuf\FieldDescriptorProto;
use google\protobuf\ServiceDescriptorProto;
use google\protobuf\EnumValueDescriptorProto;
use google\protobuf\compiler\CodeGeneratorRequest;

abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Protobuf\Configuration
     */
    protected $config;

    protected function setUp()
    {
        $this->config = new Configuration();
    }

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
     * @param array   $values
     *
     * @return \google\protobuf\FieldDescriptorProto
     */
    protected function createFieldDescriptorProto($number, $name, $type, $label, $typeName = null, array $values = [])
    {
        $field   = new FieldDescriptorProto();
        $options = isset($values['options']) ? $values['options'] : null;

        $field->setName($name);
        $field->setNumber($number);
        $field->setTypeName($typeName);
        $field->setType(FieldDescriptorProto\Type::valueOf($type));
        $field->setLabel(FieldDescriptorProto\Label::valueOf($label));

        if (isset($values['default'])) {
            $field->setDefaultValue($values['default']);
        }

        if ($options !== null) {
            $fieldOptions = new FieldOptions();

            if (isset($options['packed'])) {
                $fieldOptions->setPacked($options['packed']);
            }

            $field->setOptions($fieldOptions);
        }


        return $field;
    }

    /**
     * @param integer $number
     * @param string  $name
     *
     * @return \google\protobuf\EnumValueDescriptorProto
     */
    protected function createEnumValueDescriptorProto($number, $name)
    {
        $field = new EnumValueDescriptorProto();

        $field->setName($name);
        $field->setNumber($number);

        return $field;
    }

    /**
     * @param string $name
     * @param array  $values
     *
     * @return \google\protobuf\ServiceDescriptorProto
     */
    protected function createServiceDescriptorProto($name, array $values = [])
    {
        $descriptor = new ServiceDescriptorProto();

        $descriptor->setName($name);

        return $descriptor;
    }

    /**
     * @param string $name
     * @param array  $fields
     * @param array  $values
     *
     * @return \google\protobuf\DescriptorProto
     */
    protected function createDescriptorProto($name, array $fields, array $values = [])
    {
        $descriptor = new DescriptorProto();
        $enums      = isset($values['enums']) ? $values['enums'] : [];
        $messages   = isset($values['messages']) ? $values['messages'] : [];
        $extensions = isset($values['extensions']) ? $values['extensions'] : [];

        $descriptor->setName($name);

        foreach ($fields as $number => $field) {

            if (is_array($field)) {
                $name     = $field[0];
                $type     = $field[1];
                $label    = $field[2];
                $typeName = isset($field[3]) ? $field[3] : null;
                $values   = isset($field[4]) ? $field[4] : [];
                $field    = $this->createFieldDescriptorProto($number, $name, $type, $label, $typeName, $values);
            }

            $descriptor->addField($field);
        }

        foreach ($extensions as $number => $field) {

            if (is_array($field)) {
                $name     = $field[0];
                $type     = $field[1];
                $label    = $field[2];
                $extendee = $field[3];
                $typeName = isset($field[4]) ? $field[4] : null;
                $field    = $this->createFieldDescriptorProto($number, $name, $type, $label, $typeName);

                $field->setExtendee($extendee);
            }

            $descriptor->addExtension($field);
        }

        foreach ($enums as $enum) {
            if (is_array($enum)) {
                $name   = $enum['name'];
                $values = $enum['values'];
                $enum   = $this->createEnumDescriptorProto($name, $values);
            }

            $descriptor->addEnumType($enum);
        }

        foreach ($messages as $item) {
            if (is_array($item)) {
                $name   = $item['name'];
                $fields = isset($item['fields']) ? $item['fields'] : [];
                $values = isset($item['values']) ? $item['values'] : [];
                $item   = $this->createDescriptorProto($name, $fields, $values);
            }

            $descriptor->addNestedType($item);
        }

        return $descriptor;
    }

    /**
     * @param string $name
     * @param array  $values
     *
     * @return \google\protobuf\EnumDescriptorProto
     */
    protected function createEnumDescriptorProto($name, array $values)
    {
        $descriptor  = new EnumDescriptorProto();

        $descriptor->setName($name);

        foreach ($values as $number => $value) {

            if (is_string($value)) {
                $value  = $this->createEnumValueDescriptorProto($number, $value);
            }

            $descriptor->addValue($value);
        }

        return $descriptor;
    }

    /**
     * @param string $name
     * @param string $package
     * @param array  $values
     *
     * @return \google\protobuf\FileDescriptorProto
     */
    protected function createFileDescriptorProto($name, $package, array $values = [])
    {
        $descriptor = new FileDescriptorProto();
        $extensions = isset($values['extensions']) ? $values['extensions'] : [];
        $options    = isset($values['options']) ? $values['options'] : null;
        $messages   = isset($values['messages']) ? $values['messages'] : [];
        $services   = isset($values['services']) ? $values['services'] : [];
        $enums      = isset($values['enums']) ? $values['enums'] : [];

        $descriptor->setName($name);
        $descriptor->setPackage($package);

        foreach ($extensions as $number => $field) {
            if (is_array($field)) {
                $name     = $field[0];
                $type     = $field[1];
                $label    = $field[2];
                $extendee = $field[3];
                $typeName = isset($field[4]) ? $field[4] : null;
                $field    = $this->createFieldDescriptorProto($number, $name, $type, $label, $typeName);

                $field->setExtendee($extendee);
            }

            $descriptor->addExtension($field);
        }

        foreach ($messages as $item) {
            if (is_array($item)) {
                $name   = $item['name'];
                $fields = isset($item['fields']) ? $item['fields'] : [];
                $values = isset($item['values']) ? $item['values'] : [];
                $item   = $this->createDescriptorProto($name, $fields, $values);
            }

            $descriptor->addMessageType($item);
        }

        foreach ($enums as $item) {
            if (is_array($item)) {
                $name   = $item['name'];
                $values = isset($item['values']) ? $item['values'] : [];
                $item   = $this->createEnumDescriptorProto($name, $values);
            }

            $descriptor->addEnumType($item);
        }

        foreach ($services as $item) {
            if (is_array($item)) {
                $name   = $item['name'];
                $values = isset($item['values']) ? $item['values'] : [];
                $item   = $this->createServiceDescriptorProto($name, $values);
            }

            $descriptor->addService($item);
        }

        if ($options !== null) {
            $fileOptions = new FileOptions();
            $optionsExt  = isset($options['extensions'])
                ? $options['extensions']
                : [];

            if (isset($options['packed'])) {
                $fileOptions->setPacked($options['packed']);
            }

            foreach ($optionsExt as $ext) {
                $fileOptions->extensions()->put($ext[0], $ext[1]);
            }

            $descriptor->setOptions($fileOptions);
        }

        return $descriptor;
    }

    /**
     * @param array         $descriptors
     * @param array         $options
     * @param Configuration $Configuration
     *
     * @return \Protobuf\Compiler\Context
     */
    protected function createContext(array $descriptors, array $options = [], Configuration $config = null)
    {
        $cfg     = $config ?: $this->config;
        $opts    = Options::fromArray($options);
        $request = new CodeGeneratorRequest();

        foreach ($descriptors as $item) {
            if (is_array($item)) {
                $name     = $item['name'];
                $values   = $item['values'];
                $package  = $item['package'];
                $item     = $this->createFileDescriptorProto($name, $package, $values);
            }

            $request->addProtoFile($item);
            $request->addFileToGenerate($item->getName());
        }

        $builder  = new EntityBuilder($request);
        $entities = $builder->buildEntities();
        $context  = new Context($entities, $opts, $cfg);

        return $context;
    }

    /**
     * @param array $classes
     */
    protected function markTestIncompleteIfProtoClassNotFound(array $classes)
    {
        foreach ($classes as $class) {
            if (class_exists($class)) {
                continue;
            }

            $this->markTestIncomplete(sprintf(
                'Class "%s" not found, please run : "make proto-generate" to generate protobuf classes.',
                $class
            ));
        }
    }
}