<?php

namespace Protobuf\Compiler\Generator\Message;

use InvalidArgumentException;

use google\protobuf\DescriptorProto;
use google\protobuf\FieldDescriptorProto;
use google\protobuf\FieldDescriptorProto\Type;
use google\protobuf\FieldDescriptorProto\Label;

use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\GeneratorInterface;

use Protobuf\WireFormat;
use Protobuf\Compiler\Entity;
use Protobuf\Compiler\Generator\BaseGenerator;
use Protobuf\Compiler\Generator\GeneratorVisitor;

/**
 * Message readFrom Generator
 *
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class ReadFromGenerator extends BaseGenerator implements GeneratorVisitor
{
    /**
     * {@inheritdoc}
     */
    public function visit(Entity $entity, GeneratorInterface $class)
    {
        $class->addMethodFromGenerator($this->generateMethod($entity));
    }

    /**
     * @param \Protobuf\Compiler\Entity $entity
     *
     * @return \Zend\Code\Generator\GeneratorInterface
     */
    protected function generateMethod(Entity $entity)
    {
        $lines   = $this->generateBody($entity);
        $body    = implode(PHP_EOL, $lines);
        $method  = MethodGenerator::fromArray([
            'name'       => 'readFrom',
            'body'       => $body,
            'parameters' => [
                [
                    'name'          => 'context',
                    'type'          => '\Protobuf\ReadContext',
                ]
            ],
            'docblock'   => [
                'shortDescription' => "{@inheritdoc}"
            ]
        ]);

        return $method;
    }

    /**
     * @param \Protobuf\Compiler\Entity $entity
     *
     * @return string[]
     */
    public function generateBody(Entity $entity)
    {
        $innerLoop = $this->addIndentation($this->generateInnerLoop($entity), 1);

        $body[] = '$reader = $context->getReader();';
        $body[] = '$length = $context->getLength();';
        $body[] = '$stream = $context->getStream();';
        $body[] = null;
        $body[] = '$limit = ($length !== null)';
        $body[] = '    ? ($stream->tell() + $length)';
        $body[] = '    : null;';
        $body[] = null;
        $body[] = 'while ($limit === null || $stream->tell() < $limit) {';

        $body = array_merge($body, $innerLoop);

        $body[] = null;
        $body[] = '}';

        return $body;
    }

    /**
     * @param \Protobuf\Compiler\Entity $entity
     *
     * @return string[]
     */
    protected function generateInnerLoop(Entity $entity)
    {
        $descriptor = $entity->getDescriptor();
        $fields     = $descriptor->getFieldList() ?: [];

        $body[] = null;
        $body[] = 'if ($stream->eof()) {';
        $body[] = '    break;';
        $body[] = '}';
        $body[] = null;
        $body[] = '$key  = $reader->readVarint($stream);';
        $body[] = '$wire = \Protobuf\WireFormat::getTagWireType($key);';
        $body[] = '$tag  = \Protobuf\WireFormat::getTagFieldNumber($key);';
        $body[] = null;
        $body[] = 'if ($stream->eof()) {';
        $body[] = '    break;';
        $body[] = '}';
        $body[] = null;

        foreach ($fields as $field) {
            $lines = $this->generateFieldCondition($entity, $field);
            $body  = array_merge($body, $lines);
        }

        $unknowFieldName     = $this->getUniqueFieldName($descriptor, 'unknownFieldSet');
        $extensionsFieldName = $this->getUniqueFieldName($descriptor, 'extensions');

        $body[] = '$extensions = $context->getExtensionRegistry();';
        $body[] = '$extension  = $extensions ? $extensions->findByNumber(__CLASS__, $tag) : null;';
        $body[] = null;
        $body[] = 'if ($extension !== null) {';
        $body[] = '    $this->extensions()->put($extension, $extension->readFrom($context, $wire));';
        $body[] = null;
        $body[] = '    continue;';
        $body[] = '}';
        $body[] = null;
        $body[] = 'if ($this->' . $unknowFieldName . ' === null) {';
        $body[] = '    $this->' . $unknowFieldName . ' = new \Protobuf\UnknownFieldSet();';
        $body[] = '}';
        $body[] = null;
        $body[] = '$data    = $reader->readUnknown($stream, $wire);';
        $body[] = '$unknown = new \Protobuf\Unknown($tag, $wire, $data);';
        $body[] = null;
        $body[] = '$this->' . $unknowFieldName . '->add($unknown);';

        return $body;
    }

    /**
     * @param \Protobuf\Compiler\Entity            $entity
     * @param google\protobuf\FieldDescriptorProto $field
     *
     * @return string[]
     */
    protected function generateFieldCondition(Entity $entity, FieldDescriptorProto $field)
    {
        $tag   = $field->getNumber();
        $lines = $this->generateFieldReadStatement($entity, $field);
        $lines = $this->addIndentation($lines, 1);

        $body[] = 'if ($tag === ' . $tag . ') {';
        $body   = array_merge($body, $lines);
        $body[] = '}';
        $body[] = null;

        return $body;
    }

    /**
     * @param \Protobuf\Compiler\Entity             $entity
     * @param \google\protobuf\FieldDescriptorProto $field
     *
     * @return string[]
     */
    protected function generateFieldReadStatement(Entity $entity, FieldDescriptorProto $field)
    {
        $generator = new ReadFieldStatementGenerator($this->context);
        $statement = $generator->generateFieldReadStatement($entity, $field);

        return $statement;
    }
}
