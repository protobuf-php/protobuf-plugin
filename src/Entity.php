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
    protected $class;

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
     * @param string                               $class
     * @param \Protobuf\Message                    $descriptor
     * @param \google\protobuf\FileDescriptorProto $fileDescriptor
     */
    public function __construct($type, $class, Message $descriptor, FileDescriptorProto $fileDescriptor)
    {
        $this->type           = $type;
        $this->class          = $class;
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
        return $this->class;
    }

    /**
     * @return string
     */
    public function getName()
    {
        if (strpos($this->class, '.') === false) {
            return $this->class;
        }

        $index = strrpos($this->class, '.');
        $name  = substr($this->class, $index + 1);

        return trim($name, '.');
    }

    /**
     * @return string
     */
    public function getPackage()
    {
        if (strpos($this->class, '.') === false) {
            return null;
        }

        $index   = strrpos($this->class, '.');
        $package = substr($this->class, 0, $index);

        return trim($package, '.');
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
            $package = $extensions->get($extension);
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
}
