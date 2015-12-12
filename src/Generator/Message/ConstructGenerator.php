<?php

namespace Protobuf\Compiler\Generator\Message;

use Protobuf\Compiler\Entity;
use Protobuf\Compiler\Generator\BaseGenerator;
use Protobuf\Compiler\Generator\GeneratorVisitor;

use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\GeneratorInterface;

/**
 * Message __construct Generator
 *
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class ConstructGenerator extends BaseGenerator implements GeneratorVisitor
{
    /**
     * {@inheritdoc}
     */
    public function visit(Entity $entity, GeneratorInterface $class)
    {
        if ( ! $this->hasDefaultValue($entity)) {
            return;
        }

        $class->addMethodFromGenerator($this->generateConstructorMethod($entity));
    }

    /**
     * @param \Protobuf\Compiler\Entity $entity
     *
     * @return \Zend\Code\Generator\GeneratorInterface
     */
    protected function generateConstructorMethod(Entity $entity)
    {
        $lines   = $this->generateBody($entity);
        $body    = implode(PHP_EOL, $lines);
        $method  = MethodGenerator::fromArray([
            'name'       => '__construct',
            'body'       => $body,
            'docblock'   => [
                'shortDescription' => "Constructor"
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
        $body       = [];
        $descriptor = $entity->getDescriptor();
        $fields     = $descriptor->getFieldList() ?: [];

        foreach ($fields as $field) {
            if ( ! $field->hasDefaultValue()) {
                continue;
            }

            $name  = $field->getName();
            $value = $this->getDefaultFieldValue($field);

            $body[] = sprintf('$this->%s = %s;', $name, $value);
        }

        return $body;
    }

    /**
     * @param \Protobuf\Compiler\Entity $entity
     *
     * @return bool
     */
    public function hasDefaultValue(Entity $entity)
    {
        $descriptor = $entity->getDescriptor();
        $fields     = $descriptor->getFieldList() ?: [];

        foreach ($fields as $field) {
            if ( ! $field->hasDefaultValue()) {
                continue;
            }

            return true;
        }

        return false;
    }
}
