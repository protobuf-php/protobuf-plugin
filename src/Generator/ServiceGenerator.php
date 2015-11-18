<?php

namespace Protobuf\Compiler\Generator;

use Protobuf\Compiler\Options;
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
class ServiceGenerator extends BaseGenerator
{
    /**
     * @return string
     */
    public function generate()
    {
        $name             = $this->proto->getName();
        $namespace        = trim($this->getNamespace($this->package), '\\');
        $shortDescription = 'Protobuf service : ' . $this->proto->getName();
        $class            = InterfaceGenerator::fromArray([
            'name'          => $name,
            'namespacename' => $namespace,
            'methods'       => $this->generateMethods(),
            'docblock'      => [
                'shortDescription' => $shortDescription,
            ]
        ]);

        return $this->generateFileContent($class);
    }

    /**
     * @return string[]
     */
    protected function generateMethods()
    {
        $methods = [];

        foreach (($this->proto->getMethodList() ?: []) as $method) {
            $methods[] = $this->generateMethod($method);
        }

        return $methods;
    }

    /**
     * @param \google\protobuf\MethodDescriptorProto $method
     *
     * @return string
     */
    protected function generateMethod(MethodDescriptorProto $method)
    {
        $methodName = $method->getName();
        $inputType  = $this->getNamespace($method->getInputType());
        $outputType = $this->getNamespace($method->getOutputType());
        $method     = MethodGenerator::fromArray([
            'name'       => $methodName,
            'parameters' => [
                [
                    'name'   => 'input',
                    'type'   => $inputType
                ]
            ],
            'docblock' => [
                'tags'             => [
                    [
                        'name'        => 'param',
                        'description' =>  $inputType . ' $input'
                    ],
                    [
                        'name'        => 'return',
                        'description' =>  $outputType
                    ]
                ]
            ]
        ]);

        return $method;
    }
}
