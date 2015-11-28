<?php

namespace Protobuf\Compiler;

use Traversable;
use Doctrine\Common\Inflector\Inflector;

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
     * @var array
     */
    protected $generators;
    /**
     * @param \Protobuf\Compiler\Context $context
     */
    public function __construct(Context $context)
    {
        $this->context    = $context;
        $this->generators = [
            Entity::TYPE_ENUM      => new EnumGenerator($context),
            Entity::TYPE_SERVICE   => new ServiceGenerator($context),
            Entity::TYPE_MESSAGE   => new MessageGenerator($context),
            Entity::TYPE_EXTENSION => new ExtensionGenerator($context)
        ];
    }

    /**
     * @param Entity $entity
     */
    public function visit(Entity $entity)
    {
        $type  = $entity->getType();
        $class = $entity->getClass();
        $fqcn  = trim($this->getNamespace($class), '\\');
        $path  = $this->getPsr4ClassPath($fqcn);

        $entity->setPath($path);

        if ( ! isset($this->generators[$type])) {
            return;
        }

        $this->generators[$type]->visit($entity);
    }

    /**
     * @param string $fqcn
     *
     * @return string
     */
    protected function getPsr4ClassPath($fqcn)
    {
        $options = $this->context->getOptions();
        $psr4    = $options->getPsr4() ?: [];

        foreach ($psr4 as $prefix) {

            $length = strlen($prefix);
            $start  = substr($fqcn, 0, $length);

            if ($start !== $prefix) {
                continue;
            }

            $name = trim(str_replace($prefix, '', $fqcn), '\\');
            $path = $this->getClassPath($name);

            return $path;
        }

        return $this->getClassPath($fqcn);
    }

    /**
     * @param string $fqcn
     *
     * @return string
     */
    protected function getClassPath($fqcn)
    {
        return str_replace('\\', DIRECTORY_SEPARATOR, $fqcn) . '.php';
    }
}
