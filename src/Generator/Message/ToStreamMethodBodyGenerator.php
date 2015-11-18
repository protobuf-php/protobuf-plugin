<?php

namespace Protobuf\Compiler\Generator\Message;

use Protobuf\Compiler\Options;
use google\protobuf\DescriptorProto;
use google\protobuf\FieldDescriptorProto;
use Protobuf\Compiler\Generator\BaseGenerator;

/**
 * Message toStream Body Generator
 *
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class ToStreamMethodBodyGenerator extends BaseGenerator
{
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
