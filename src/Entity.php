<?php

namespace Protobuf\Compiler;

use Protobuf\Message;
use google\protobuf\php\Extension;
use google\protobuf\FileDescriptorProto;

/**
 * Generated PHP class
 *
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class Entity
{
    const TYPE_EXTENSION = 'extension';
    const TYPE_MESSAGE   = 'message';
    const TYPE_SERVICE   = 'service';
    const TYPE_ENUM      = 'enum';

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $parent;

    /**
     * @var string
     */
    protected $content;

    /**
     * @var \Protobuf\Message
     */
    protected $descriptor;

    /**
     * @var \google\protobuf\FileDescriptorProto
     */
    protected $fileDescriptor;

    /**
     * @var bool
     */
    protected $fileToGenerate = false;

    /**
     * @param string                               $type
     * @param string                               $name
     * @param \Protobuf\Message                    $descriptor
     * @param \google\protobuf\FileDescriptorProto $fileDescriptor
     * @param string                               $parent
     */
    public function __construct($type, $name, Message $descriptor, FileDescriptorProto $fileDescriptor, $parent = null)
    {
        $this->type           = $type;
        $this->name           = $name;
        $this->parent         = $parent;
        $this->descriptor     = $descriptor;
        $this->fileDescriptor = $fileDescriptor;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param string $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * @return string
     */
    public function getClass()
    {
        $name    = $this->getName();
        $package = $this->getPackage();

        return$this->fullyQualifiedName($package, $name);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getPackage()
    {
        $parent  = $this->parent;
        $package = $this->fileDescriptor->getPackage();

        return $this->fullyQualifiedName($package, $parent);
    }

    /**
     * @return string
     */
    public function getNamespace()
    {
        $package    = $this->getPackage();
        $extension  = Extension::package();
        $extensions = $this->getFileOptionsExtensions();

        if ($extensions !== null && $extensions->offsetExists($extension)) {
            $package = $this->fullyQualifiedName($extensions->get($extension), $this->parent);
        }

        if ($package === null) {
            return $package;
        }

        return str_replace('.', '\\', $package);
    }

    /**
     * @return string
     */
    public function getNamespacedName()
    {
        $namespace = $this->getNamespace();
        $name      = $this->getName();

        if ($namespace === null) {
            return '\\' . $name;
        }

        return '\\' . $namespace . '\\' . $name;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * @return bool
     */
    public function isFileToGenerate()
    {
        return $this->fileToGenerate;
    }

    /**
     * @param bool $flag
     */
    public function setFileToGenerate($flag)
    {
        $this->fileToGenerate = $flag;
    }

    /**
     * @return \Protobuf\Message
     */
    public function getDescriptor()
    {
        return $this->descriptor;
    }

    /**
     * @return \google\protobuf\FileDescriptorProto
     */
    public function getFileDescriptor()
    {
        return $this->fileDescriptor;
    }

    /**
     * @return \Protobuf\Extension\ExtensionFieldMap
     */
    protected function getFileOptionsExtensions()
    {
        if ( ! $this->fileDescriptor->hasOptions()) {
            return null;
        }

        return $this->fileDescriptor->getOptions()->extensions();
    }

    /**
     * @return string
     */
    protected function fullyQualifiedName()
    {
        $args  = func_get_args();
        $parts = array_filter($args);
        $name  = implode('.', $parts);

        return $name;
    }
}
