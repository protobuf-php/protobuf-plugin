<?php

namespace Protobuf\Compiler\Generator\Message;

use google\protobuf\DescriptorProto;
use google\protobuf\FieldDescriptorProto;

use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\GeneratorInterface;

use Protobuf\Compiler\Entity;
use Protobuf\Compiler\Generator\BaseGenerator;
use Protobuf\Compiler\Generator\GeneratorVisitor;

/**
 * Message fromStream generator
 *
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class FromStreamGenerator extends BaseGenerator implements GeneratorVisitor
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
     * @return string
     */
    protected function generateMethod(Entity $entity)
    {
        $lines   = $this->generateBody($entity);
        $body    = implode(PHP_EOL, $lines);
        $method  = MethodGenerator::fromArray([
            'name'       => 'fromStream',
            'body'       => $body,
            'static'     => true,
            'parameters' => [
                [
                    'name'          => 'stream',
                    'type'          => 'mixed',
                ],
                [
                    'name'          => 'configuration',
                    'type'          => '\Protobuf\Configuration',
                    'defaultValue'  => null
                ]
            ],
            'docblock'   => [
                'shortDescription' => "{@inheritdoc}"
            ]
        ]);

        return $method;
    }

    /**
     * @return string[]
     */
    public function generateBody()
    {
        return [
            'return new self($stream, $configuration);'
        ];
    }
}
