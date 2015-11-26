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
     * @param \google\protobuf\compiler\CodeGeneratorRequest $request
     * @param \google\protobuf\FileDescriptorProto           $proto
     *
     * @return \Protobuf\Compiler\Options
     */
    protected function createOptions(CodeGeneratorRequest $request, FileDescriptorProto $proto)
    {
        $parameter = $request->getParameter();
        $options   = [];

        parse_str($parameter, $options);

        if ( ! isset($options['package'])) {
            $options['package'] = $proto->getPackage();
        }

        return Options::fromArray($options);
    }

    /**
     * @param \Protobuf\Stream $stream
     *
     * @return \Protobuf\Stream
     */
    public function compile(Stream $stream)
    {
        // Parse the request
        $response = new CodeGeneratorResponse();
        $request  = CodeGeneratorRequest::fromStream($stream, $this->config);

        $protoList    = $request->getProtoFileList() ?: [];
        $generateList = $request->hasFileToGenerateList()
            ? $request->getFileToGenerateList()->getArrayCopy()
            : [];

        // Run each file
        foreach ($protoList as $file) {

            $options          = $this->createOptions($request, $file);
            $generateImported = $options->getGenerateImported();

            // Only compile those given to generate, not the imported ones
            if ( ! $generateImported && ! in_array($file->getName(), $generateList)) {
                $this->logger->info(sprintf('Skipping generation of imported file "%s"', $file->getName()));

                continue;
            }

            $this->logger->info(sprintf('Generating proto file "%s"', $file->getName()));

            $generator = new Generator($file, $options);
            $result    = $generator->generate($file);

            foreach ($result as $path => $content) {
                $this->logger->info(sprintf('Generating class "%s"', $path));

                $file = new File();

                $file->setName($path);
                $file->setContent($content);

                $response->addFile($file);
            }
        }

        // Finally serialize the response object
        return $response->toStream($this->config);
    }

    /**
     * @return \Protobuf\Configuration
     */
    public static function defaultConfig()
    {
        $config   = Configuration::getInstance();
        $registry = $config->getExtensionRegistry();

        //require_once '/Users/fsilva/backup/workspace/php/protobuf/google-protobuf-proto/src/php/Extension.php';

        Extension::registerAllExtensions($registry);

        return $config;
    }
}
