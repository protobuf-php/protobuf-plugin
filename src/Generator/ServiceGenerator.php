<?php

namespace Protobuf\Compiler\Generator;

use Protobuf\Compiler\Entity;

use google\protobuf\MethodDescriptorProto;
use google\protobuf\ServiceDescriptorProto;

use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\InterfaceGenerator;

/**
 * Service interface Generator
 *
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class ServiceGenerator extends BaseGenerator implements EntityVisitor
{
    /**
     * {@inheritdoc}
     */
    public function visit(Entity $entity)
    {
        $name             = $entity->getName();
        $namespace        = $entity->getNamespace();
        $shortDescription = 'Protobuf service : ' . $entity->getClass();
        $class            = InterfaceGenerator::fromArray([
            'name'          => $name,
            'namespacename' => $namespace,
            'methods'       => $this->generateMethods($entity),
            'docblock'      => [
                'shortDescription' => $shortDescription,
            ]
        ]);

        $entity->setContent($this->generateFileContent($class, $entity));
    }

    /**
     * @param \Protobuf\Compiler\Entity $entity
     *
     * @return string[]
     */
    protected function generateMethods(Entity $entity)
    {
        $result     = [];
        $descriptor = $entity->getDescriptor();
        $methods    = $descriptor->getMethodList() ?: [];

        foreach ($methods as $method) {
            $result[] = $this->generateMethod($entity, $method);
        }

        return $result;
    }

    /**
     * @param \Protobuf\Compiler\Entity              $entity
     * @param \google\protobuf\MethodDescriptorProto $method
     *
     * @return string
     */
    protected function generateMethod(Entity $entity, MethodDescriptorProto $method)
    {
        $methodName  = $method->getName();
        $descriptor  = $entity->getDescriptor();
        $input       = $this->getEntity($method->getInputType());
        $output      = $this->getEntity($method->getOutputType());
        $inputClass  = $input->getNamespacedName();
        $outputClass = $output->getNamespacedName();
        $method      = MethodGenerator::fromArray([
            'name'       => $methodName,
            'parameters' => [
                [
                    'name'   => 'input',
                    'type'   => $inputClass
                ]
            ],
            'docblock' => [
                'tags'             => [
                    [
                        'name'        => 'param',
                        'description' =>  $inputClass . ' $input'
                    ],
                    [
                        'name'        => 'return',
                        'description' =>  $outputClass
                    ]
                ]
            ]
        ]);

        return $method;
    }
}
