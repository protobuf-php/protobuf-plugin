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
        $package    = $fileDescriptor->getPackage();
        $result     = [];

        if ($messages !== null) {
            $result = array_merge($result, $this->generateMessages($fileDescriptor, $messages, $package));
        }

        if ($services !== null) {
            $result = array_merge($result, $this->generateServices($fileDescriptor, $services, $package));
        }

        if ($enums !== null) {
            $result = array_merge($result, $this->generateEnums($fileDescriptor, $enums, $package));
        }

        if ($this->hasExtension($fileDescriptor)) {
            $result[] = $this->generateExtension($fileDescriptor, $package);
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
     * @param string                               $package
     *
     * @return array
     */
    protected function generateMessages(FileDescriptorProto $fileDescriptor, Traversable $messages, $package)
    {
        $result = [];

        foreach ($messages as $message) {
            $entity        = $this->generateMessage($fileDescriptor, $message, $package);
            $innerMessages = $message->getNestedTypeList();
            $innerEnums    = $message->getEnumTypeList();
            $innerPackage  = $entity->getClass();

            $result[] = $entity;

            if ($innerMessages) {
                $result = array_merge($result, $this->generateMessages($fileDescriptor, $innerMessages, $innerPackage));
            }

            if ($innerEnums) {
                $result = array_merge($result, $this->generateEnums($fileDescriptor, $innerEnums, $innerPackage));
            }
        }

        return $result;
    }

    /**
     * @param \google\protobuf\FileDescriptorProto $fileDescriptor
     * @param \Traversable                         $services
     * @param string                               $package
     *
     * @return array
     */
    protected function generateServices(FileDescriptorProto $fileDescriptor, Traversable $services, $package)
    {
        $result = [];

        foreach ($services as $service) {
            $result[] = $this->generateService($fileDescriptor, $service, $package);
        }

        return $result;
    }

    /**
     * @param \google\protobuf\FileDescriptorProto $fileDescriptor
     * @param \Traversable                         $enums
     * @param string                               $package
     *
     * @return array
     */
    protected function generateEnums(FileDescriptorProto $fileDescriptor, Traversable $enums, $package)
    {
        $result = [];

        foreach ($enums as $enum) {
            $result[] = $this->generateEnum($fileDescriptor, $enum, $package);
        }

        return $result;
    }

    /**
     * @param \google\protobuf\FileDescriptorProto $fileDescriptor
     * @param string                               $package
     *
     * @return \Protobuf\Compiler\Entity
     */
    protected function generateExtension(FileDescriptorProto $fileDescriptor, $package)
    {
        $name   = 'Extension';
        $type   = Entity::TYPE_EXTENSION;
        $class  = $package . '.' . $name;
        $entity = new Entity($type, $class, $fileDescriptor, $fileDescriptor);

        return $entity;
    }

    /**
     * @param \google\protobuf\FileDescriptorProto $fileDescriptor
     * @param \google\protobuf\EnumDescriptorProto $enumDescriptor
     * @param string                               $package
     *
     * @return \Protobuf\Compiler\Entity
     */
    protected function generateEnum(FileDescriptorProto $fileDescriptor, EnumDescriptorProto $enumDescriptor, $package)
    {
        $type   = Entity::TYPE_ENUM;
        $class  = $package . '.' . $enumDescriptor->getName();
        $entity = new Entity($type, $class, $enumDescriptor, $fileDescriptor);

        return $entity;
    }

    /**
     * @param \google\protobuf\FileDescriptorProto    $fileDescriptor
     * @param \google\protobuf\ServiceDescriptorProto $serviceDescriptor
     * @param string                                  $package
     *
     * @return \Protobuf\Compiler\Entity
     */
    protected function generateService(FileDescriptorProto $fileDescriptor, ServiceDescriptorProto $serviceDescriptor, $package)
    {
        $type   = Entity::TYPE_SERVICE;
        $class  = $package . '.' . $serviceDescriptor->getName();
        $entity = new Entity($type, $class, $serviceDescriptor, $fileDescriptor);

        return $entity;
    }

    /**
     * @param \google\protobuf\FileDescriptorProto $fileDescriptor
     * @param \google\protobuf\DescriptorProto     $messageDescriptor
     * @param string                               $package
     *
     * @return \Protobuf\Compiler\Entity
     */
    protected function generateMessage(FileDescriptorProto $fileDescriptor, DescriptorProto $messageDescriptor, $package)
    {
        $type   = Entity::TYPE_MESSAGE;
        $class  = $package . '.' . $messageDescriptor->getName();
        $entity = new Entity($type, $class, $messageDescriptor, $fileDescriptor);

        return $entity;
    }
}
