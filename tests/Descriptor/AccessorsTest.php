<?php

namespace ProtobufCompilerTest\Descriptor;

use Protobuf\Extension\ExtensionFieldMap;
use Protobuf\Binary\SizeCalculator;
use Protobuf\ComputeSizeContext;
use Protobuf\Stream;

use ProtobufCompilerTest\TestCase;
use ProtobufCompilerTest\Protos\Simple;
use ProtobufCompilerTest\Protos\Person;
use ProtobufCompilerTest\Protos\Repeated;
use ProtobufCompilerTest\Protos\Person\PhoneType;
use ProtobufCompilerTest\Protos\Person\PhoneNumber;
use ProtobufCompilerTest\Protos\AddressBook;

/**
 * @group functional
 */
class AccessorsTest extends TestCase
{
    protected function setUp()
    {
        $this->markTestIncompleteIfProtoClassNotFound();

        parent::setUp();
    }

    public function testSimpleMessageAccessors()
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

        $simple->setBool(null);
        $simple->setFloat(null);
        $simple->setString(null);
        $simple->setUint32(null);
        $simple->setInt32(null);
        $simple->setFixed32(null);
        $simple->setSint32(null);
        $simple->setSfixed32(null);
        $simple->setDouble(null);
        $simple->setInt64(null);
        $simple->setUint64(null);
        $simple->setFixed64(null);
        $simple->setSint64(null);
        $simple->setBytes(null);
        $simple->setSfixed64(null);

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

    public function testSimpleMessageFromArray()
    {
        $simple = Simple::fromArray([
            'bool'      => true,
            'string'    => "foo",
            'bytes'     => "bar",
            'float'     => 12345.123,
            'fixed32'   => 123456789,
            'uint32'    => 123456789,
            'sfixed32'  => -123456789,
            'sint32'    => -123456789,
            'int32'     => -123456789,
            'double'    => 123456789.12345,
            'int64'     => -123456789123456789,
            'uint64'    => 123456789123456789,
            'fixed64'   => 123456789123456789,
            'sfixed64'  => -123456789123456789,
            'sint64'    => -123456789123456789
        ]);

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

        $simple = Simple::fromArray([]);

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

    public function testComplexMessageFromArray()
    {
        $phone1  = PhoneNumber::fromArray([
            'number' => '1231231212',
            'type'   => PhoneType::HOME()
        ]);

        $phone2  = PhoneNumber::fromArray([
            'number' => '55512321312',
            'type'   => PhoneType::MOBILE()
        ]);

        $phone3  = PhoneNumber::fromArray([
            'number' => '3493123123',
            'type'   => PhoneType::WORK()
        ]);

        $person1  = Person::fromArray([
            'id'    => 2051,
            'name'  => 'John Doe',
            'email' => 'john.doe@gmail.com',
            'phone' => [$phone1, $phone2]
        ]);

        $person2  = Person::fromArray([
            'id'    => 23,
            'name'  => 'Iván Montes',
            'email' => 'drslump@pollinimini.net',
            'phone' => [$phone3]
        ]);

        $book = AddressBook::fromArray([
            'person' => [$person1, $person2]
        ]);

        $this->assertInstanceOf(AddressBook::CLASS, $book);
        $this->assertCount(2, $book->getPersonList());

        $p1 = $book->getPersonList()[0];
        $p2 = $book->getPersonList()[1];

        $this->assertSame($person1, $p1);
        $this->assertSame($person2, $p2);

        $this->assertEquals($p1->getId(), 2051);
        $this->assertEquals($p1->getName(), 'John Doe');

        $this->assertEquals($p2->getId(), 23);
        $this->assertEquals($p2->getName(), 'Iván Montes');

        $this->assertCount(2, $p1->getPhoneList());
        $this->assertCount(1, $p2->getPhoneList());

        $this->assertEquals($p1->getPhoneList()[0]->getNumber(), '1231231212');
        $this->assertEquals($p1->getPhoneList()[0]->getType(), PhoneType::HOME());

        $this->assertEquals($p1->getPhoneList()[1]->getNumber(), '55512321312');
        $this->assertEquals($p1->getPhoneList()[1]->getType(), PhoneType::MOBILE());

        $this->assertEquals($p2->getPhoneList()[0]->getNumber(), '3493123123');
        $this->assertEquals($p2->getPhoneList()[0]->getType(), PhoneType::WORK());
    }

    public function testComplexMessageFromArrayDefaults()
    {
        $phone = PhoneNumber::fromArray([
            'number' => '1231231212'
        ]);

        $this->assertEquals($phone->getNumber(), '1231231212');
        $this->assertEquals($phone->getType(), PhoneType::HOME());
    }

    public function testComplexMessageFromArrayRequired()
    {
        $person = Person::fromArray([
            'id'    => 2051,
            'name'  => 'John Doe'
        ]);

        $this->assertEquals($person->getId(), 2051);
        $this->assertEquals($person->getName(), 'John Doe');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Field "name" (tag 1) is required but has no value.
     */
    public function testComplexMessageFromArrayRequiredException()
    {
        Person::fromArray([
            'id' => 2051
        ]);
    }

    public function testStreamSetterWrapsValue()
    {
        $simple1 = new Simple();
        $simple2 = new Simple();
        $handle  = fopen('php://temp', 'w+');

        fwrite($handle, 'foo');

        $simple1->setBytes("bar");
        $simple2->setBytes($handle);

        $this->assertInstanceOf('Protobuf\Stream', $simple1->getBytes());
        $this->assertInstanceOf('Protobuf\Stream', $simple2->getBytes());

        $this->assertEquals('bar', (string) $simple1->getBytes());
        $this->assertEquals('foo', (string) $simple2->getBytes());
    }

    public function testRepeatedMessageAccessorsCollection()
    {
        $repeated = new Repeated();
        $nested   = new Repeated\Nested();

        $intList    = $this->getMock('Protobuf\ScalarCollection');
        $stringList = $this->getMock('Protobuf\ScalarCollection');
        $nestedList = $this->getMock('Protobuf\MessageCollection');
        $bytesList  = $this->getMock('Protobuf\StreamCollection');

        $this->assertNull($repeated->getIntList());
        $this->assertNull($repeated->getBytesList());
        $this->assertNull($repeated->getStringList());
        $this->assertNull($repeated->getNestedList());

        $repeated->setIntList($intList);
        $repeated->setBytesList($bytesList);
        $repeated->setStringList($stringList);
        $repeated->setNestedList($nestedList);

        $this->assertSame($intList, $repeated->getIntList());
        $this->assertSame($bytesList, $repeated->getBytesList());
        $this->assertSame($stringList, $repeated->getStringList());
        $this->assertSame($nestedList, $repeated->getNestedList());

        $intList->expects($this->once())
            ->method('add')
            ->with($this->equalTo(1));

        $bytesList->expects($this->once())
            ->method('add')
            ->with($this->callback(function ($value) {
                $this->assertInstanceOf('Protobuf\Stream', $value);
                $this->assertEquals('bin', (string) $value);

                return true;
            }));

        $stringList->expects($this->once())
            ->method('add')
            ->with($this->equalTo('one'));

        $nestedList->expects($this->once())
            ->method('add')
            ->with($this->equalTo($nested));

        $nested->setId(1);
        $repeated->addInt(1);
        $repeated->addBytes('bin');
        $repeated->addString('one');
        $repeated->addNested($nested);

        $this->assertSame($intList, $repeated->getIntList());
        $this->assertSame($bytesList, $repeated->getBytesList());
        $this->assertSame($stringList, $repeated->getStringList());
        $this->assertSame($nestedList, $repeated->getNestedList());

        $repeated->setIntList(null);
        $repeated->setBytesList(null);
        $repeated->setStringList(null);
        $repeated->setNestedList(null);

        $this->assertNull($repeated->getIntList());
        $this->assertNull($repeated->getBytesList());
        $this->assertNull($repeated->getStringList());
        $this->assertNull($repeated->getNestedList());
    }
}
