<?php

namespace Protobuf\Compiler;

use Protobuf\Stream;
use Protobuf\Configuration;

use Psr\Log\LoggerInterface;

use Protobuf\Compiler\Options;
use Protobuf\Compiler\Generator;

use google\protobuf\php\Extension;
use google\protobuf\FileDescriptorProto;
use google\protobuf\compiler\CodeGeneratorRequest;
use google\protobuf\compiler\CodeGeneratorResponse;
use google\protobuf\compiler\CodeGeneratorResponse\File;

/**
 * Compiler
 *
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class Compiler
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Protobuf\Configuration
     */
    protected $config;

    /**
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Protobuf\Configuration  $config
     */
    public function __construct(LoggerInterface $logger, Configuration $config = null)
    {
        $this->logger = $logger;
        $this->config = $config ?: self::defaultConfig();
    }

    /**
     * @param \Protobuf\Stream $stream
     *
     * @return \Protobuf\Stream
     */
    public function compile(Stream $stream)
    {
        // Parse the request
        $request    = CodeGeneratorRequest::fromStream($stream, $this->config);
        $tempname   = tempnam(sys_get_temp_dir(), 'proto') . '.php';
        $response   = new CodeGeneratorResponse();
        $context    = $this->createContext($request);
        $entities   = $context->getEntities();
        $options    = $context->getOptions();
        $generator  = new Generator($context);
        $regenerate = false;

        // Run each entity
        foreach ($entities as $key => $entity) {
            $generateImported = $options->getGenerateImported();
            $isFileToGenerate = $entity->isFileToGenerate();

            // Only compile those given to generate, not the imported ones
            if ( ! $generateImported && ! $isFileToGenerate) {
                $this->logger->debug(sprintf('Skipping generation of imported class "%s"', $entity->getClass()));

                continue;
            }

            $this->logger->info(sprintf('Generating class "%s"', $entity->getClass()));

            $generator->visit($entity);

            $file    = new File();
            $type    = $entity->getType();
            $path    = $entity->getPath();
            $content = $entity->getContent();
            $class   = trim($entity->getNamespacedName(), '\\');

            if ( ! class_exists($class) && ! interface_exists($class)) {

                $this->logger->info(sprintf('Loading class "%s"', $class));

                file_put_contents($tempname, $content);

                include $tempname;

                if ($type === Entity::TYPE_EXTENSION) {

                    echo $class . PHP_EOL;

                    $this->logger->info(sprintf('Registering extension "%s"', $class));

                    $config   = $this->config;
                    $registry = $config->getExtensionRegistry();

                    $class::registerAllExtensions($registry);

                    $regenerate = true;
                }
            }

            $file->setName($path);
            $file->setContent($content);

            $response->addFile($file);
        }

        if (is_file($tempname)) {
            @unlink($tempname);
        }

        if ($regenerate) {

            $this->logger->info('Regenerating classes with new extensions');

            $stream->seek(0);

            // Renegerate classes with new extensions
            return $this->compile($stream);
        }

        $this->logger->info('Generation completed.');

        // Finally serialize the response object
        return $response->toStream($this->config);
    }

    /**
     * @param \google\protobuf\compiler\CodeGeneratorRequest $request
     *
     * @return \Protobuf\Compiler\Context
     */
    public function createContext(CodeGeneratorRequest $request)
    {
        $options  = $this->createOptions($request);
        $entities = $this->createEntities($request);
        $context  = new Context($entities, $options, $this->config);

        return $context;
    }

    /**
     * @param \google\protobuf\compiler\CodeGeneratorRequest $request
     *
     * @return array
     */
    protected function createEntities(CodeGeneratorRequest $request)
    {
        $builder  = new EntityBuilder($request);
        $entities = $builder->buildEntities();

        return $entities;
    }

    /**
     * @param \google\protobuf\compiler\CodeGeneratorRequest $request
     *
     * @return \Protobuf\Compiler\Options
     */
    protected function createOptions(CodeGeneratorRequest $request)
    {
        $parameter = $request->getParameter();
        $options   = [];

        parse_str($parameter, $options);

        return Options::fromArray($options);
    }

    /**
     * @return \Protobuf\Configuration
     */
    public static function defaultConfig()
    {
        $config   = Configuration::getInstance();
        $registry = $config->getExtensionRegistry();

        Extension::registerAllExtensions($registry);

        return $config;
    }
}
