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

                $entity->setFileDescriptor($descriptor);
                $entity->setFileToGenerate($toGenerate);

                $result[$entity->getClass()] = $entity;
            }
        }

        return $result;
    }

    /**
     * @param \google\protobuf\FileDescriptorProto $descriptor
     *
     * @return array
     */
    protected function buildFileEntities(FileDescriptorProto $descriptor)
    {
        $messages   = $descriptor->getMessageTypeList();
        $enums      = $descriptor->getEnumTypeList();
        $services   = $descriptor->getServiceList();
        $package    = $descriptor->getPackage();
        $result     = [];

        if ($messages !== null) {
            $result = array_merge($result, $this->generateMessages($messages, $package));
        }

        if ($services !== null) {
            $result = array_merge($result, $this->generateServices($services, $package));
        }

        if ($enums !== null) {
            $result = array_merge($result, $this->generateEnums($enums, $package));
        }

        if ($this->hasExtension($descriptor)) {
            $result[] = $this->generateExtension($descriptor, $package);
        }

        return $result;
    }

    /**
     * @param \google\protobuf\FileDescriptorProto $descriptor
     *
     * @return boolean
     */
    protected function hasExtension($descriptor)
    {
        $messages     = $descriptor->getMessageTypeList();
        $hasExtension = $descriptor->hasExtensionList();

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
     * @param \Traversable $messages
     * @param string       $package
     *
     * @return array
     */
    protected function generateMessages($messages, $package)
    {
        $result = [];

        foreach ($messages as $message) {
            $entity        = $this->generateMessageClass($message, $package);
            $innerMessages = $message->getNestedTypeList();
            $innerEnums    = $message->getEnumTypeList();
            $innerPackage  = $entity->getClass();

            $result[] = $entity;

            if ($innerMessages) {
                $result = array_merge($result, $this->generateMessages($innerMessages, $innerPackage));
            }

            if ($innerEnums) {
                $result = array_merge($result, $this->generateEnums($innerEnums, $innerPackage));
            }
        }

        return $result;
    }

    /**
     * @param \Traversable $services
     * @param string       $package
     *
     * @return array
     */
    protected function generateServices($services, $package)
    {
        $result = [];

        foreach ($services as $service) {
            $result[] = $this->generateServiceClass($service, $package);
        }

        return $result;
    }

    /**
     * @param \Traversable $enums
     * @param string       $package
     *
     * @return array
     */
    protected function generateEnums($enums, $package)
    {
        $result = [];

        foreach ($enums as $enum) {
            $result[] = $this->generateEnumClass($enum, $package);
        }

        return $result;
    }

    /**
     * @param \google\protobuf\FileDescriptorProto $descriptor
     * @param string                               $package
     *
     * @return \Protobuf\Compiler\Entity
     */
    protected function generateExtension(FileDescriptorProto $descriptor, $package)
    {
        $name   = 'Extension';
        $type   = Entity::TYPE_EXTENSION;
        $class  = $package . '.' . $name;
        $entity = new Entity($type, $class, $descriptor);

        return $entity;
    }

    /**
     * @param \google\protobuf\EnumDescriptorProto $descriptor
     * @param string                               $package
     *
     * @return \Protobuf\Compiler\Entity
     */
    protected function generateEnumClass(EnumDescriptorProto $descriptor, $package)
    {
        $type   = Entity::TYPE_ENUM;
        $name   = $descriptor->getName();
        $class  = $package . '.' . $name;
        $entity = new Entity($type, $class, $descriptor);

        return $entity;
    }

    /**
     * @param \google\protobuf\ServiceDescriptorProto $descriptor
     * @param string                                  $package
     *
     * @return \Protobuf\Compiler\Entity
     */
    protected function generateServiceClass(ServiceDescriptorProto $descriptor, $package)
    {
        $type   = Entity::TYPE_SERVICE;
        $name   = $descriptor->getName();
        $class  = $package . '.' . $name;
        $entity = new Entity($type, $class, $descriptor);

        return $entity;
    }

    /**
     * @param \google\protobuf\DescriptorProto $descriptor
     * @param string                           $package
     *
     * @return \Protobuf\Compiler\Entity
     */
    protected function generateMessageClass(DescriptorProto $descriptor, $package)
    {
        $type   = Entity::TYPE_MESSAGE;
        $name   = $descriptor->getName();
        $class  = $package . '.' . $name;
        $entity = new Entity($type, $class, $descriptor);

        return $entity;
    }
}
