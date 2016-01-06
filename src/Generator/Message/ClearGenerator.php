<?php

namespace Protobuf\Compiler\Generator\Message;

use Protobuf\Compiler\Entity;
use Protobuf\Compiler\Generator\BaseGenerator;
use Protobuf\Compiler\Generator\GeneratorVisitor;

use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\GeneratorInterface;

/**
 * Message clear method generator
 *
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class ClearGenerator extends BaseGenerator implements GeneratorVisitor
{
    /**
     * {@inheritdoc}
     */
    public function visit(Entity $entity, GeneratorInterface $class)
    {
        $class->addMethodFromGenerator($this->generateClearMethod($entity));
    }

    /**
     * @param \Protobuf\Compiler\Entity $entity
     *
     * @return \Zend\Code\Generator\GeneratorInterface
     */
    protected function generateClearMethod(Entity $entity)
    {
        $lines   = $this->generateBody($entity);
        $body    = implode(PHP_EOL, $lines);
        $method  = MethodGenerator::fromArray([
            'name'       => 'clear',
            'body'       => $body,
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
        $body       = [];
        $descriptor = $entity->getDescriptor();
        $fields     = $descriptor->getFieldList() ?: [];

        foreach ($fields as $field) {
            $name  = $field->getName();
            $value = $this->getDefaultFieldValue($field);

            $body[] = sprintf('$this->%s = %s;', $name, $value);
        }

        return $body;
    }
}
