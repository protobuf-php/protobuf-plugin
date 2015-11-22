<?php

namespace Protobuf\Compiler\Generator\Message;

use Protobuf\Compiler\Options;
use google\protobuf\DescriptorProto;
use google\protobuf\FieldDescriptorProto;
use Protobuf\Compiler\Generator\BaseGenerator;

/**
 * Message annotations
 *
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class AnnotationGenerator extends BaseGenerator
{
    /**
     * @return string[]
     */
    public function generateAnnotation()
    {
        $fields     = [];
        $extensions = [];
        $package    = json_encode($this->package);
        $name       = json_encode($this->proto->getName());

        foreach (($this->proto->getFieldList() ?: []) as $field) {
            $annot  = $this->generateFieldAnnotation($field);
            $annot  = $this->addIndentation($annot, 2, '  ');
            $fields = array_merge($fields, $annot);
        }

        foreach (($this->proto->getExtensionList() ?: []) as $field) {
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
