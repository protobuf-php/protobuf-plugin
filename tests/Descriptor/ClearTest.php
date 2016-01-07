<?php

namespace ProtobufCompilerTest\Descriptor;

use Protobuf\Extension\ExtensionFieldMap;
use Protobuf\Binary\SizeCalculator;
use Protobuf\ComputeSizeContext;
use Protobuf\Stream;

use ProtobufCompilerTest\TestCase;
use ProtobufCompilerTest\Protos\Simple;
use ProtobufCompilerTest\Protos\Person\PhoneType;
use ProtobufCompilerTest\Protos\Person\PhoneNumber;

/**
 * @group functional
 */
class ClearTest extends TestCase
{
    protected function setUp()
    {
        $this->markTestIncompleteIfProtoClassNotFound([
            'ProtobufCompilerTest\Protos\Simple',
            'ProtobufCompilerTest\Protos\Person\PhoneType',
            'ProtobufCompilerTest\Protos\Person\PhoneNumber'
        ]);

        parent::setUp();
    }

    public function testSimpleMessageClear()
    {
        $simple = new Simple();

        $simple->setBool(true);
        $simple->setString("foo");
        $simple->setFloat(12345.123);
        $simple->setUint32(123456789);
        $simple->setInt32(-123456789);
        $simple->setFixed32(123456789);
        $simple->setSint32(-123456789);
        $simple->setSfixed32(-123456789);
        $simple->setDouble(123456789.12345);
        $simple->setInt64(-123456789123456789);
        $simple->setUint64(123456789123456789);
        $simple->setFixed64(123456789123456789);
        $simple->setSint64(-123456789123456789);
        $simple->setBytes(Stream::wrap("bar"));
        $simple->setSfixed64(-123456789123456789);

        $this->assertSame(true, $simple->getBool());
        $this->assertSame("foo", $simple->getString());
        $this->assertSame(12345.123, $simple->getFloat());
        $this->assertSame(123456789, $simple->getUint32());
        $this->assertSame(-123456789, $simple->getInt32());
        $this->assertSame(123456789, $simple->getFixed32());
        $this->assertSame(-123456789, $simple->getSint32());
        $this->assertSame(-123456789, $simple->getSfixed32());
        $this->assertSame(123456789.12345, $simple->getDouble());
        $this->assertSame(-123456789123456789, $simple->getInt64());
        $this->assertSame(123456789123456789, $simple->getUint64());
        $this->assertSame(123456789123456789, $simple->getFixed64());
        $this->assertSame(-123456789123456789, $simple->getSint64());
        $this->assertSame(-123456789123456789, $simple->getSfixed64());
        $this->assertInstanceOf('Protobuf\Stream', $simple->getBytes());

        $simple->clear();

        $this->assertNull($simple->getBool());
        $this->assertNull($simple->getString());
        $this->assertNull($simple->getFloat());
        $this->assertNull($simple->getUint32());
        $this->assertNull($simple->getInt32());
        $this->assertNull($simple->getFixed32());
        $this->assertNull($simple->getSint32());
        $this->assertNull($simple->getSfixed32());
        $this->assertNull($simple->getDouble());
        $this->assertNull($simple->getInt64());
        $this->assertNull($simple->getUint64());
        $this->assertNull($simple->getFixed64());
        $this->assertNull($simple->getSint64());
        $this->assertNull($simple->getSfixed64());
        $this->assertNull($simple->getBytes());
    }

    public function testClearMessageWithDefaultValue()
    {
        $phone = new PhoneNumber();

        $this->assertNull($phone->getNumber());
        $this->assertSame(PhoneType::HOME(), $phone->getType());

        $phone->setNumber('1231231212');
        $phone->setType(PhoneType::MOBILE());

        $this->assertEquals('1231231212', $phone->getNumber());
        $this->assertSame(PhoneType::MOBILE(), $phone->getType());

        $phone->clear();

        $this->assertNull($phone->getNumber());
        $this->assertSame(PhoneType::HOME(), $phone->getType());
    }
}
