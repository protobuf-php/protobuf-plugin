<?php

namespace ProtobufCompilerTest\Descriptor;

use Protobuf\Extension\ExtensionFieldMap;
use Protobuf\Binary\SizeCalculator;
use Protobuf\ComputeSizeContext;
use Protobuf\Stream;

use ProtobufCompilerTest\TestCase;
use ProtobufCompilerTest\Protos\Simple;
use ProtobufCompilerTest\Protos\Person;

/**
 * @group functional
 */
class MergeTest extends TestCase
{
    protected function setUp()
    {
        $this->markTestIncompleteIfProtoClassNotFound();

        parent::setUp();
    }

    public function testSimpleMessageMerge()
    {
        $simple1 = new Simple();
        $simple2 = new Simple();
        $bytes   = Stream::wrap("bar");

        $simple1->setBool(true);
        $simple1->setString("foo");
        $simple1->setBytes($bytes);
        $simple1->setFloat(12345.123);
        $simple1->setUint32(123456789);
        $simple1->setInt32(-123456789);
        $simple1->setFixed32(123456789);
        $simple1->setSint32(-123456789);
        $simple1->setSfixed32(-123456789);
        $simple1->setDouble(123456789.12345);
        $simple1->setInt64(-123456789123456789);
        $simple1->setUint64(123456789123456789);
        $simple1->setFixed64(123456789123456789);
        $simple1->setSint64(-123456789123456789);
        $simple1->setSfixed64(-123456789123456789);

        $simple2->merge($simple1);

        $this->assertSame(true, $simple2->getBool());
        $this->assertSame("foo", $simple2->getString());
        $this->assertSame($bytes, $simple2->getBytes());
        $this->assertSame(12345.123, $simple2->getFloat());
        $this->assertSame(123456789, $simple2->getUint32());
        $this->assertSame(-123456789, $simple2->getInt32());
        $this->assertSame(123456789, $simple2->getFixed32());
        $this->assertSame(-123456789, $simple2->getSint32());
        $this->assertSame(-123456789, $simple2->getSfixed32());
        $this->assertSame(123456789.12345, $simple2->getDouble());
        $this->assertSame(-123456789123456789, $simple2->getInt64());
        $this->assertSame(123456789123456789, $simple2->getUint64());
        $this->assertSame(123456789123456789, $simple2->getFixed64());
        $this->assertSame(-123456789123456789, $simple2->getSint64());
        $this->assertSame(-123456789123456789, $simple2->getSfixed64());
    }

    public function testSimpleMessageMergeNullComparison()
    {
        $simple1 = new Simple();
        $simple2 = new Simple();
        $bytes   = Stream::wrap("bar");

        $simple1->setBool(false);
        $simple1->setFloat(0.0);
        $simple1->setUint32(0);

        $simple2->merge($simple1);

        $this->assertSame(false, $simple2->getBool());
        $this->assertSame(0.0, $simple2->getFloat());
        $this->assertSame(0, $simple2->getUint32());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Argument 1 passed to ProtobufCompilerTest\Protos\Simple::merge must be a ProtobufCompilerTest\Protos\Simple, ProtobufCompilerTest\Protos\Person given
     */
    public function testMergeException()
    {
        $simple = new Simple();
        $person = new Person();

        $simple->merge($person);
    }
}
