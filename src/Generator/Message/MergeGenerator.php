<?php

namespace Protobuf\Compiler\Generator\Message;

use google\protobuf\DescriptorProto;
use google\protobuf\FieldDescriptorProto;
use google\protobuf\FieldDescriptorProto\Label;

use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\GeneratorInterface;

use Protobuf\Compiler\Entity;
use Protobuf\Compiler\Generator\BaseGenerator;
use Protobuf\Compiler\Generator\GeneratorVisitor;

/**
 * Message merge method generator
 *
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class MergeGenerator extends BaseGenerator implements GeneratorVisitor
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
     * @return MethodGenerator
     */
    protected function generateMethod(Entity $entity)
    {
        $lines  = $this->generateBody($entity);
        $body   = implode(PHP_EOL, $lines);
        $method = MethodGenerator::fromArray([
            'name'       => 'merge',
            'body'       => $body,
            'parameters' => [
                [
                    'name' => 'message',
                    'type' => '\Protobuf\Message'
                ]
            ],
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
    public function generateBody(Entity $entity)
    {
        $lines      = [];
        $descriptor = $entity->getDescriptor();
        $class      = $entity->getNamespacedName();
        $fields     = $descriptor->getFieldList() ?: [];
        $message    = var_export("Argument 1 passed to %s must be a %s, %s given", true);
        $exception  = 'sprintf(' . $message . ', __METHOD__, __CLASS__, get_class($message))';

        $lines[] = 'if ( ! $message instanceof ' . $class . ') {';
        $lines[] = '    throw new \InvalidArgumentException(' . $exception . ');';
        $lines[] = '}';
        $lines[] = null;

        foreach ($fields as $field) {
            $item  = $this->generateFieldMerge($entity, $field);
            $lines = array_merge($lines, $item);
        }

        return $lines;
    }

    /**
     * @param \Protobuf\Compiler\Entity             $entity
     * @param \google\protobuf\FieldDescriptorProto $field
     *
     * @return array
     */
    protected function generateFieldMerge(Entity $entity, FieldDescriptorProto $field)
    {
        $lines     = [];
        $fieldName = $field->getName();
        $format    = '$this->%s = $message->%s ?: $this->%s;';
        $lines[]   = sprintf($format, $fieldName, $fieldName, $fieldName);

        return $lines;
    }
}
