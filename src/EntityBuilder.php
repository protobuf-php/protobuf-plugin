<?php

namespace Protobuf\Compiler;

use Traversable;
use google\protobuf\DescriptorProto;
use google\protobuf\FileDescriptorProto;
use google\protobuf\EnumDescriptorProto;
use google\protobuf\FieldDescriptorProto;
use google\protobuf\ServiceDescriptorProto;
use google\protobuf\compiler\CodeGeneratorRequest;

/**
 * Entity Builder
 *
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class EntityBuilder
{
    /**
     * @var \google\protobuf\compiler\CodeGeneratorRequest
     */
    protected $request;

    /**
     * @param \google\protobuf\compiler\CodeGeneratorRequest $request
     */
    public function __construct(CodeGeneratorRequest $request)
    {
        $this->request = $request;
    }

    /**
     * @return array
     */
    public function getFileToGenerateMap()
    {
        $map  = [];
        $list = $this->request->getFileToGenerateList();

        if ($list === null) {
            return $map;
        }

        foreach ($list as $fileName) {
            $map[$fileName] = $fileName;
        }

        return $map;
    }

    /**
     * @return array
     */
    public function buildEntities()
    {
        $result      = [];
        $generateMap = $this->getFileToGenerateMap();
        $protoList   = $this->request->getProtoFileList();

        if ($protoList === null) {
            return $result;
        }

        foreach ($protoList as $descriptor) {

            $fileName   = $descriptor->getName();
            $toGenerate = isset($generateMap[$fileName]);

            foreach ($this->buildFileEntities($descriptor) as $entity) {

                $entity->setFileToGenerate($toGenerate);

                $result[$entity->getClass()] = $entity;
            }
        }

        return $result;
    }

    /**
     * @param \google\protobuf\FileDescriptorProto $fileDescriptor
     *
     * @return array
     */
    protected function buildFileEntities(FileDescriptorProto $fileDescriptor)
    {
        $messages   = $fileDescriptor->getMessageTypeList();
        $enums      = $fileDescriptor->getEnumTypeList();
        $services   = $fileDescriptor->getServiceList();
        $result     = [];

        if ($messages !== null) {
            $result = array_merge($result, $this->generateMessages($fileDescriptor, $messages));
        }

        if ($services !== null) {
            $result = array_merge($result, $this->generateServices($fileDescriptor, $services));
        }

        if ($enums !== null) {
            $result = array_merge($result, $this->generateEnums($fileDescriptor, $enums));
        }

        if ($this->hasExtension($fileDescriptor)) {
            $result[] = $this->generateExtension($fileDescriptor);
        }

        return $result;
    }

    /**
     * @param \google\protobuf\FileDescriptorProto $fileDescriptor
     *
     * @return boolean
     */
    protected function hasExtension(FileDescriptorProto $fileDescriptor)
    {
        $messages     = $fileDescriptor->getMessageTypeList();
        $hasExtension = $fileDescriptor->hasExtensionList();

        if ($hasExtension) {
            return true;
        }

        if ($messages === null) {
            return false;
        }

        foreach ($messages as $message) {
            if ($message->hasExtensionList()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \google\protobuf\FileDescriptorProto $fileDescriptor
     * @param \Traversable                         $messages
     * @param string                               $parent
     *
     * @return array
     */
    protected function generateMessages(FileDescriptorProto $fileDescriptor, Traversable $messages, $parent = null)
    {
        $result = [];

        foreach ($messages as $message) {
            $entity        = $this->generateMessage($fileDescriptor, $message, $parent);
            $innerMessages = $message->getNestedTypeList();
            $innerEnums    = $message->getEnumTypeList();
            $innerParent   = ($parent !== null)
                ? $parent . '.' . $entity->getName()
                : $entity->getName();

            $result[] = $entity;

            if ($innerMessages) {
                $result = array_merge($result, $this->generateMessages($fileDescriptor, $innerMessages, $innerParent));
            }

            if ($innerEnums) {
                $result = array_merge($result, $this->generateEnums($fileDescriptor, $innerEnums, $innerParent));
            }
        }

        return $result;
    }

    /**
     * @param \google\protobuf\FileDescriptorProto $fileDescriptor
     * @param \Traversable                         $services
     * @param string                               $parent
     *
     * @return array
     */
    protected function generateServices(FileDescriptorProto $fileDescriptor, Traversable $services, $parent = null)
    {
        $result = [];

        foreach ($services as $service) {
            $result[] = $this->generateService($fileDescriptor, $service, $parent);
        }

        return $result;
    }

    /**
     * @param \google\protobuf\FileDescriptorProto $fileDescriptor
     * @param \Traversable                         $enums
     * @param string                               $parent
     *
     * @return array
     */
    protected function generateEnums(FileDescriptorProto $fileDescriptor, Traversable $enums, $parent = null)
    {
        $result = [];

        foreach ($enums as $enum) {
            $result[] = $this->generateEnum($fileDescriptor, $enum, $parent);
        }

        return $result;
    }

    /**
     * @param \google\protobuf\FileDescriptorProto $fileDescriptor
     * @param string                               $parent
     *
     * @return \Protobuf\Compiler\Entity
     */
    protected function generateExtension(FileDescriptorProto $fileDescriptor, $parent = null)
    {
        $name   = 'Extension';
        $type   = Entity::TYPE_EXTENSION;
        $entity = new Entity($type, $name, $fileDescriptor, $fileDescriptor, $parent);

        return $entity;
    }

    /**
     * @param \google\protobuf\FileDescriptorProto $fileDescriptor
     * @param \google\protobuf\EnumDescriptorProto $enumDescriptor
     * @param string                               $parent
     *
     * @return \Protobuf\Compiler\Entity
     */
    protected function generateEnum(FileDescriptorProto $fileDescriptor, EnumDescriptorProto $enumDescriptor, $parent = null)
    {
        $type   = Entity::TYPE_ENUM;
        $name   = $enumDescriptor->getName();
        $entity = new Entity($type, $name, $enumDescriptor, $fileDescriptor, $parent);

        return $entity;
    }

    /**
     * @param \google\protobuf\FileDescriptorProto    $fileDescriptor
     * @param \google\protobuf\ServiceDescriptorProto $serviceDescriptor
     * @param string                                  $parent
     *
     * @return \Protobuf\Compiler\Entity
     */
    protected function generateService(FileDescriptorProto $fileDescriptor, ServiceDescriptorProto $serviceDescriptor, $parent = null)
    {
        $type   = Entity::TYPE_SERVICE;
        $name   = $serviceDescriptor->getName();
        $entity = new Entity($type, $name, $serviceDescriptor, $fileDescriptor, $parent);

        return $entity;
    }

    /**
     * @param \google\protobuf\FileDescriptorProto $fileDescriptor
     * @param \google\protobuf\DescriptorProto     $messageDescriptor
     * @param string                               $parent
     *
     * @return \Protobuf\Compiler\Entity
     */
    protected function generateMessage(FileDescriptorProto $fileDescriptor, DescriptorProto $messageDescriptor, $parent = null)
    {
        $type   = Entity::TYPE_MESSAGE;
        $name   = $messageDescriptor->getName();
        $entity = new Entity($type, $name, $messageDescriptor, $fileDescriptor, $parent);

        return $entity;
    }
}
