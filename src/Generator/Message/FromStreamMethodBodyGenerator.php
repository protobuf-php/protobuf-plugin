<?php

namespace Protobuf\Compiler\Generator\Message;

use Protobuf\Compiler\Options;
use google\protobuf\DescriptorProto;
use google\protobuf\FieldDescriptorProto;
use Protobuf\Compiler\Generator\BaseGenerator;

/**
 * Message fromStream Body Generator
 *
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class FromStreamMethodBodyGenerator extends BaseGenerator
{
    /**
     * @return string[]
     */
    public function generateBody()
    {
        $package   = $this->package . '.' . $this->proto->getName();
        $className = $this->getNamespace($package);

        $body[] = '$config  = $configuration ?: \Protobuf\Configuration::getInstance();';
        $body[] = '$context = $config->createReadContext($stream);';
        $body[] = '$message = new ' . $className . '();';
        $body[] = null;
        $body[] = '$message->readFrom($context);';
        $body[] = null;
        $body[] = 'return $message;';

        return $body;
    }
}
