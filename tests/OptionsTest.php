<?php

namespace ProtobufCompilerTest;

use Protobuf\Compiler\Options;

class OptionsTest extends TestCase
{
    public function testFromArray()
    {
        $options1 = Options::fromArray([
            'generate-imported' => 1,
            'verbose'           => 1,
            'psr4'              => ['MyPackage'],
        ]);

        $options2 = Options::fromArray([
            'generate-imported' => 0,
            'verbose'           => 0
        ]);

        $this->assertTrue($options1->getVerbose());
        $this->assertTrue($options1->getGenerateImported());
        $this->assertEquals(['MyPackage'], $options1->getPsr4());

        $this->assertFalse($options2->getVerbose());
        $this->assertFalse($options2->getGenerateImported());
    }

    public function testDefaults()
    {
        $options = Options::fromArray([]);

        $this->assertFalse($options->getVerbose());
        $this->assertFalse($options->getGenerateImported());
    }
}
