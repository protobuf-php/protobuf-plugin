<?php

namespace Protobuf\Compiler\Generator\Message;

use Protobuf\Compiler\Entity;
use Protobuf\Compiler\Generator\BaseGenerator;
use Protobuf\Compiler\Generator\GeneratorVisitor;

use google\protobuf\DescriptorProto;
use google\protobuf\FieldDescriptorProto;

use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\GeneratorInterface;

/**
 * Message descriptor generator
 *
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class DescriptorGenerator extends BaseGenerator implements GeneratorVisitor
{
    /**
     * {@inheritdoc}
     */
    public function visit(Entity $entity, GeneratorInterface $class)
    {
        $class->addMethodFromGenerator($this->generateMethod($entity));
    }


    /**
     * @param \Protobuf\Compiler\Entity $entity
     *
     * @return string[]
     */
    protected function generateMethod(Entity $entity)
    {
        $lines   = $this->generateBody($entity);
        $body    = implode(PHP_EOL, $lines);
        $method  = MethodGenerator::fromArray([
            'name'       => 'descriptor',
            'body'       => $body,
            'static'     => true,
            'docblock'   => [
                'shortDescription' => "{@inheritdoc}"
            ]
        ]);

        return $method;
    }

    /**
     * @param \Protobuf\Compiler\Entity $entity
     *
     * @return string[]
     */
    protected function generateBody(Entity $entity)
    {
        $lines      = [];
        $fields     = [];
        $extensions = [];
        $descriptor = $entity->getDescriptor();

        if ($descriptor->hasOptions()) {
            $lines   = $this->createOptionsBody($entity);
            $lines[] = null;
        }

        foreach (($descriptor->getFieldList() ?: []) as $field) {
            $values = $this->generateFieldBody($field);
            $fields = array_merge($fields, $this->addIndentation($values, 2));
        }

        foreach (($descriptor->getExtensionList() ?: []) as $field) {
            $values     = $this->generateFieldBody($field);
            $extensions = array_merge($extensions, $this->addIndentation($values, 2));
        }

        $lines[] = "return \google\protobuf\DescriptorProto::fromArray([";
        $lines[] = "    'name'      => " . var_export($descriptor->getName(), true) . ",";

        if ( ! empty($fields)) {
            $lines[] = "    'field'     => [";
            $lines   = array_merge($lines, $fields);
            $lines[] = "    ],";
        }

        if ( ! empty($extensions)) {
            $lines[] = "    'extension' => [";
            $lines   = array_merge($lines, $extensions);
            $lines[] = "    ],";
        }

        if ($descriptor->hasOptions()) {
            $lines[] = "    'options' => \$options";
        }

        $lines[] = "]);";

        return $lines;
    }

    /**
     * @param \google\protobuf\FieldDescriptorProto $field
     *
     * @return string[]
     */
    protected function generateFieldBody(FieldDescriptorProto $field)
    {
        $lines     = [];
        $name      = $field->getName();
        $number    = $field->getNumber();
        $typeName  = $field->getTypeName();
        $extendee  = $field->getExtendee();
        $type      = $field->getType()->name();
        $label     = $field->getLabel()->name();
        $default   = $this->getDefaultFieldValue($field);
        $values    = [
            'number'    => var_export($number, true),
            'name'      => var_export($name, true),
            'type'      => '\google\protobuf\FieldDescriptorProto\Type::' . $type . '()',
            'label'     => '\google\protobuf\FieldDescriptorProto\Label::' . $label . '()',
        ];

        if ($extendee) {
            $values['extendee'] = var_export($extendee, true);
        }

        if ($typeName) {
            $values['type_name'] = var_export($typeName, true);
        }

        if ($field->hasDefaultValue()) {
            $values['default_value'] = $default;
        }

        $lines[] = '\google\protobuf\FieldDescriptorProto::fromArray([';
        $lines   = array_merge($lines, $this->generateArrayLines($values));
        $lines[] = ']),';

        return $lines;
    }

    /**
     * @param \Protobuf\Compiler\Entity $entity
     *
     * @return string[]
     */
    protected function createOptionsBody(Entity $entity)
    {
        $descriptor = $entity->getDescriptor();
        $values     = [];
        $lines      = [];

        $options    = $descriptor->getOptions();
        $extensions = $options->extensions();

        $lines[] = '$options = \google\protobuf\MessageOptions::fromArray([';
        $lines   = array_merge($lines, $this->generateArrayLines($values));
        $lines[] = ']);';
        $lines[] = null;

        for ($extensions->rewind(); $extensions->valid(); $extensions->next()) {
            $extension = $extensions->current();
            $info      = $extensions->getInfo();

            $method = '\\' . $extension->getMethod();
            $value  = var_export($info, true);
            $name   = $extension->getName();

            if (is_object($info)) {
                $value = '\\' . $value;
            }

            $lines[] = '$options->extensions()->add(' . $method . '(), ' . $value . ');';
        }

        return $lines;
    }
}
