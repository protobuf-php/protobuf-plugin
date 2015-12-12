<?php

namespace Protobuf\Compiler\Generator;

use Zend\Code\Generator\ClassGenerator;

use Protobuf\Compiler\Entity;
use Protobuf\Compiler\Context;

use Protobuf\Compiler\Generator\Message\FieldsGenerator;
use Protobuf\Compiler\Generator\Message\WriteToGenerator;
use Protobuf\Compiler\Generator\Message\ReadFromGenerator;
use Protobuf\Compiler\Generator\Message\ToStreamGenerator;
use Protobuf\Compiler\Generator\Message\ConstructGenerator;
use Protobuf\Compiler\Generator\Message\FromStreamGenerator;
use Protobuf\Compiler\Generator\Message\AnnotationGenerator;
use Protobuf\Compiler\Generator\Message\ExtensionsGenerator;
use Protobuf\Compiler\Generator\Message\SerializedSizeGenerator;

/**
 * Message Generator
 *
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class MessageGenerator extends BaseGenerator implements EntityVisitor
{
    /**
     * @var array
     */
    protected $generators;

    /**
     * @param \Protobuf\Compiler\Context $context
     */
    public function __construct(Context $context)
    {
        parent::__construct($context);

        $this->generators = [
            new AnnotationGenerator($context),
            new ConstructGenerator($context),
            new FieldsGenerator($context),
            new ExtensionsGenerator($context),
            new FromStreamGenerator($context),
            new ToStreamGenerator($context),
            new WriteToGenerator($context),
            new ReadFromGenerator($context),
            new SerializedSizeGenerator($context)
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function visit(Entity $entity)
    {
        $name             = $entity->getName();
        $namespace        = $entity->getNamespace();
        $descriptor       = $entity->getDescriptor();
        $shortDescription = 'Protobuf message : ' . $entity->getClass();
        $class            = ClassGenerator::fromArray([
            'name'          => $name,
            'namespacename' => $namespace,
            'extendedClass' => '\Protobuf\AbstractMessage',
            'docblock'      => [
                'shortDescription' => $shortDescription
            ]
        ]);

        foreach ($this->generators as $generator) {
            $generator->visit($entity, $class);
        }

        $entity->setContent($this->generateFileContent($class, $entity));
    }
}
