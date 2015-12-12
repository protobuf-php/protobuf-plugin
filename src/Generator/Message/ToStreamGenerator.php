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
 * Message toStream Generator
 *
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class ToStreamGenerator extends BaseGenerator implements GeneratorVisitor
{
    /**
     * {@inheritdoc}
     */
    public function visit(Entity $entity, GeneratorInterface $class)
    {
        $class->addMethodFromGenerator($this->generateToStreamMethod($entity));
    }

    /**
     * @param \Protobuf\Compiler\Entity $entity
     *
     * @return \Zend\Code\Generator\GeneratorInterface
     */
    protected function generateToStreamMethod(Entity $entity)
    {
        $lines   = $this->generateBody($entity);
        $body    = implode(PHP_EOL, $lines);
        $method  = MethodGenerator::fromArray([
            'name'       => 'toStream',
            'body'       => $body,
            'parameters' => [
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
        $body[] = '$config  = $configuration ?: \Protobuf\Configuration::getInstance();';
        $body[] = '$context = $config->createWriteContext();';
        $body[] = '$stream  = $context->getStream();';
        $body[] = null;
        $body[] = '$this->writeTo($context);';
        $body[] = '$stream->seek(0);';
        $body[] = null;
        $body[] = 'return $stream;';

        return $body;
    }
}
