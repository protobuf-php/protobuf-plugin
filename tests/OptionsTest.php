<?php

namespace ProtobufCompilerTest;

use Protobuf\Compiler\Options;

class OptionsTest extends TestCase
{
    public function testFromArray()
    {
        $options1 = Options::fromArray([
            'skip-imported' => 1,
            'verbose'       => 1,
            'package'       => 'MyPackage',
            'psr4'          => ['MyPackage'],
        ]);

        $options2 = Options::fromArray([
            'skip-imported' => 0,
            'verbose'       => 0,
            'package'       => 'OtherPackage',
        ]);

        $this->assertTrue($options1->getVerbose());
        $this->assertTrue($options1->getSkipImported());
        $this->assertEquals(['MyPackage'], $options1->getPsr4());
        $this->assertEquals('MyPackage', $options1->getPackage());

        $this->assertFalse($options2->getVerbose());
        $this->assertFalse($options2->getSkipImported());
        $this->assertEquals('OtherPackage', $options2->getPackage());
    }

    public function testDefaults()
    {
        $options = Options::fromArray([]);

        $this->assertNull($options->getPackage());
        $this->assertFalse($options->getVerbose());
        $this->assertFalse($options->getSkipImported());
    }
}
