<?php

namespace Protobuf\Compiler\Generator\Message;

use Protobuf\Compiler\Entity;
use Protobuf\Compiler\Generator\BaseGenerator;
use Protobuf\Compiler\Generator\GeneratorVisitor;

use google\protobuf\DescriptorProto;
use google\protobuf\FieldDescriptorProto;

use Zend\Code\Generator\GeneratorInterface;

/**
 * Message annotations
 *
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class AnnotationGenerator extends BaseGenerator implements GeneratorVisitor
{
    /**
     * {@inheritdoc}
     */
    public function visit(Entity $entity, GeneratorInterface $class)
    {
        $body = $this->generateAnnotation($entity);
        $desc = implode(PHP_EOL, $body);

        $class->getDocblock()->setLongDescription($desc);
    }

    /**
     * @param \Protobuf\Compiler\Entity $entity
     *
     * @return string[]
     */
    public function generateAnnotation(Entity $entity)
    {
        $fields     = [];
        $extensions = [];
        $descriptor = $entity->getDescriptor();
        $name       = json_encode($entity->getName());
        $package    = json_encode($entity->getPackage());

        foreach (($descriptor->getFieldList() ?: []) as $field) {
            $annot  = $this->generateFieldAnnotation($field);
            $annot  = $this->addIndentation($annot, 2, '  ');
            $fields = array_merge($fields, $annot);
        }

        foreach (($descriptor->getExtensionList() ?: []) as $field) {
            $annot      = $this->generateFieldAnnotation($field);
            $annot      = $this->addIndentation($annot, 2, '  ');
            $extensions = array_merge($extensions, $annot);
        }

        if ( ! empty($fields)) {
            $index = count($fields) -1;
            $value = $fields[$index];

            $fields[$index] = trim($value, ',');
        }

        if ( ! empty($extensions)) {
            $index = count($extensions) -1;
            $value = $extensions[$index];

            $extensions[$index] = trim($value, ',');
        }

        $lines[] = "@\Protobuf\Annotation\Descriptor(";
        $lines[] = "  name=$name,";
        $lines[] = "  package=$package,";
        $lines[] = "  fields={";
        $lines   = array_merge($lines, $fields);
        $lines[] = "  },";
        $lines[] = "  extensions={";
        $lines   = array_merge($lines, $extensions);
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
        $extendee  = $field->getExtendee();
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

        if ($extendee) {
            $mapping['extendee'] = trim($extendee, '.');
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
}
