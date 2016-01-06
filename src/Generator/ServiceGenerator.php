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
        $inputClass  = $this->getMethodInputTypeHint($method);
        $inputDoc    = $this->getMethodInputDocblock($method);
        $outputDoc   = $this->getMethodOutputDocblock($method);
        $methodName  = $this->getCamelizedValue($method->getName());
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
                        'description' =>  $inputDoc . ' $input'
                    ],
                    [
                        'name'        => 'return',
                        'description' =>  $outputDoc
                    ]
                ]
            ]
        ]);

        return $method;
    }

    /**
     * @param \google\protobuf\MethodDescriptorProto $method
     *
     * @return string
     */
    protected function getMethodInputTypeHint(MethodDescriptorProto $method)
    {
        $refType   = $method->getInputType();
        $refEntity = $this->getEntity($refType);

        if ($method->getClientStreaming()) {
            return '\Iterator';
        }

        return $refEntity->getNamespacedName();
    }

    /**
     * @param \google\protobuf\MethodDescriptorProto $method
     *
     * @return string
     */
    protected function getMethodOutputTypeHint(MethodDescriptorProto $method)
    {
        $refType   = $method->getOutputType();
        $refEntity = $this->getEntity($refType);

        if ($method->getServerStreaming()) {
            return '\Iterator';
        }

        return $refEntity->getNamespacedName();
    }

    /**
     * @param \google\protobuf\MethodDescriptorProto $method
     *
     * @return string
     */
    protected function getMethodInputDocblock(MethodDescriptorProto $method)
    {
        $refType   = $method->getInputType();
        $refEntity = $this->getEntity($refType);
        $refClass  = $this->getMethodInputTypeHint($method);

        if ($method->getClientStreaming()) {
            return sprintf('\Iterator<%s>', $refEntity->getNamespacedName());
        }

        return $refClass;
    }

    /**
     * @param \google\protobuf\MethodDescriptorProto $method
     *
     * @return string
     */
    protected function getMethodOutputDocblock(MethodDescriptorProto $method)
    {
        $refType   = $method->getOutputType();
        $refEntity = $this->getEntity($refType);
        $refClass  = $this->getMethodOutputTypeHint($method);

        if ($method->getServerStreaming()) {
            return sprintf('\Iterator<%s>', $refEntity->getNamespacedName());
        }

        return $refClass;
    }
}
