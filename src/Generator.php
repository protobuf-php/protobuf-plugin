<?php

namespace Protobuf\Compiler;

use Traversable;
use Doctrine\Common\Inflector\Inflector;

use google\protobuf\DescriptorProto;
use google\protobuf\FileDescriptorProto;
use google\protobuf\EnumDescriptorProto;
use google\protobuf\FieldDescriptorProto;
use google\protobuf\ServiceDescriptorProto;

use Protobuf\Compiler\Generator\BaseGenerator;
use Protobuf\Compiler\Generator\EnumGenerator;
use Protobuf\Compiler\Generator\ServiceGenerator;
use Protobuf\Compiler\Generator\MessageGenerator;
use Protobuf\Compiler\Generator\ExtensionGenerator;

/**
 * Generator
 *
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class Generator extends BaseGenerator
{
    /**
     * @var \google\protobuf\DescriptorProto
     */
    protected $proto;

    /**
     * @param \google\protobuf\FileDescriptorProto $proto
     * @param \Protobuf\Compiler\Options           $options
     */
    public function __construct(FileDescriptorProto $proto, Options $options)
    {
        $this->proto   = $proto;
        $this->options = $options;
    }

    /**
     * @return array
     */
    public function generate()
    {
        $messages   = $this->proto->getMessageTypeList() ?: [];
        $extensions = $this->proto->getExtensionList() ?: [];
        $enums      = $this->proto->getEnumTypeList() ?: [];
        $services   = $this->proto->getServiceList() ?: [];
        $package    = $this->options->getPackage();
        $result     = [];
        $files      = [];

        // Generate Enums
        $result += $this->generateEnums($enums, $package);
        $result += $this->generateServices($services, $package);
        $result += $this->generateMessages($messages, $package);
        $result += $this->generateExtensions($extensions, $package);

        foreach ($result as $class => $content) {
            $fqcn = trim($this->getNamespace($class), '\\');
            $name = $this->getPsr4ClassName($fqcn);
            $path = str_replace('\\', DIRECTORY_SEPARATOR, $name) . '.php';

            $files[$path] = $content;
        }

        return $files;
    }

    /**
     * @param string $fqcn
     *
     * @return string
     */
    protected function getPsr4ClassName($fqcn)
    {
        $psr4 = $this->options->getPsr4() ?: [];

        foreach ($psr4 as $prefix) {

            $length = strlen($prefix);
            $start  = substr($fqcn, 0, $length);

            if ($start !== $prefix) {
                continue;
            }

            return trim(str_replace($prefix, '', $fqcn), '\\');
        }

        return $fqcn;
    }

    /**
     * @param \Traversable $extensions
     * @param string       $package
     *
     * @return array
     */
    public function generateExtensions($extensions, $package)
    {
        $result = [];

        foreach ($extensions as $extension) {
            $name    = Inflector::classify($extension->getName());
            $class   = $extension->getExtendee() . '.' . $name;
            $content = $this->generateExtensionClass($extension, $package);

            $result[$class] = $content;
        }

        return $result;
    }

    /**
     * @param \Traversable $messages
     * @param string       $package
     *
     * @return array
     */
    public function generateMessages($messages, $package)
    {
        $result = [];

        foreach ($messages as $message) {
            $name       = $message->getName();
            $class      = $package . '.' . $name;
            $enums      = $message->getEnumTypeList() ?: [];
            $messages   = $message->getNestedTypeList() ?: [];
            $extensions = $message->getExtensionList() ?: [];
            $content    = $this->generateMessageClass($message, $package);

            $result[$class] = $content;

            $result += $this->generateEnums($enums, $class);
            $result += $this->generateMessages($messages, $class);
            $result += $this->generateExtensions($extensions, $class);
        }

        return $result;
    }

    /**
     * @param \Traversable $enums
     * @param string       $package
     *
     * @return array
     */
    public function generateEnums($enums, $package)
    {
        $result = [];

        foreach ($enums as $enum) {
            $name    = $enum->getName();
            $class   = $package . '.' . $name;
            $content = $this->generateEnumClass($enum, $package);

            $result[$class] = $content;
        }

        return $result;
    }

    /**
     * @param \google\protobuf\EnumDescriptorProto $enum
     * @param string                               $package
     *
     * @return string
     */
    public function generateEnumClass(EnumDescriptorProto $enum, $package)
    {
        $generator = new EnumGenerator($enum, $this->options, $package);
        $content   = $generator->generate();

        return $content;
    }

    /**
     * @param \Traversable $services
     * @param string       $package
     *
     * @return array
     */
    public function generateServices($services, $package)
    {
        $result = [];
        foreach ($services as $service) {
            $name    = $service->getName();
            $class   = $package . '.' . $name;
            $content = $this->generateServiceClass($service, $package);

            $result[$class] = $content;
        }

        return $result;
    }

    /**
     * @param \google\protobuf\ServiceDescriptorProto $service
     * @param string                                  $package
     *
     * @return string
     */
    public function generateServiceClass(ServiceDescriptorProto $service, $package)
    {
        $generator = new ServiceGenerator($service, $this->options, $package);
        $content   = $generator->generate();

        return $content;
    }

    /**
     * @param \google\protobuf\DescriptorProto $message
     * @param string                           $package
     *
     * @return string
     */
    public function generateMessageClass(DescriptorProto $message, $package)
    {
        $generator = new MessageGenerator($message, $this->options, $package);
        $content   = $generator->generate();

        return $content;
    }

    /**
     * @param \google\protobuf\FieldDescriptorProto $extension
     * @param string                                $package
     *
     * @return string
     */
    public function generateExtensionClass(FieldDescriptorProto $extension, $package)
    {
        $generator = new ExtensionGenerator($extension, $this->options, $package);
        $content   = $generator->generate();

        return $content;
    }
}
