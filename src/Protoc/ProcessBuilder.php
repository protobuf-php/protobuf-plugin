<?php

namespace Protobuf\Compiler\Protoc;

use SplFileInfo;
use RuntimeException;
use InvalidArgumentException;
use UnexpectedValueException;
use Symfony\Component\Process\Process;

/**
 * Protoc Process Builder
 *
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class ProcessBuilder
{
    /**
     * @var string
     */
    protected $plugin;

    /**
     * @var string
     */
    protected $protoc;

    /**
     * @var string
     */
    protected $version;

    /**
     * @var bool
     */
    protected $includeDescriptors = false;

    /**
     * @var array
     */
    protected $descriptorPaths = [];

    /**
     * @param string $plugin
     * @param string $protoc
     * @param string $version
     */
    public function __construct($plugin, $protoc, $version = '2.3.0')
    {
        $this->plugin  = $plugin;
        $this->protoc  = $protoc;
        $this->version = $version;
    }

    /**
     * @param bool $flag
     */
    public function setIncludeDescriptors($flag)
    {
        $this->includeDescriptors = $flag;
    }

    /**
     * @param array $paths
     */
    public function setDescriptorPaths($paths)
    {
        $this->descriptorPaths = $paths;
    }

    /**
     * Assert min protoc version.
     *
     * @throws \UnexpectedValueException Indicates a problem with protoc.
     */
    public function assertVersion()
    {
        $process = $this->createProtocVersionProcess();

        // Check if protoc is available
        $process->mustRun();

        $return = $process->getExitCode();
        $result = trim($process->getOutput());

        if (0 !== $return && 1 !== $return) {
            throw new UnexpectedValueException("Unable to find the protoc command. Please make sure it's installed and available in the path.");
        }

        if ( ! preg_match('/[0-9\.]+/', $result, $match)) {
            throw new UnexpectedValueException("Unable to get protoc command version. Please make sure it's installed and available in the path.");
        }

        if (version_compare($match[0], $this->version) < 0) {
            throw new UnexpectedValueException("The protoc command in your system is too old. Minimum version required is '{$this->version}' but found '$result'.");
        }
    }

    /**
     * Create a process
     *
     * @param string|SplFileInfo     $outPath
     * @param string[]|SplFileInfo[] $protosFiles
     * @param string[]|SplFileInfo[] $includeDirs
     * @param string[]|SplFileInfo[] $parameters
     *
     * @return \Symfony\Component\Process\Process
     *
     * @throws \InvalidArgumentException
     */
    public function createProcess($outPath, array $protosFiles, array $includeDirs, array $parameters)
    {
        if (empty($protosFiles)) {
            throw new InvalidArgumentException('Proto file list cannot be empty.');
        }

        $outDir  = $this->getRealPath($outPath);
        $include = $this->getRealPaths($includeDirs);
        $protos  = $this->getRealPaths($protosFiles, true);

        $commandLine = "";

        $commandLine .= $this->protoc;

        $commandLine .= " ".sprintf('--plugin=protoc-gen-php=%s', $this->plugin);

        foreach ($include as $i) {
            $commandLine .= " ".sprintf('--proto_path=%s', $i);
        }

        if ($this->includeDescriptors) {
            $commandLine .= " ".sprintf('--proto_path=%s', $this->findDescriptorsPath());
        }

        // Protoc will pass custom arguments to the plugin if they are given
        // before a colon character. ie: --php_out="foo=bar:/path/to/plugin"
        $out = ( ! empty($parameters))
            ? http_build_query($parameters, '', '&') . ':' . $outDir
            : $outDir;

        $commandLine .= " ".sprintf('--php_out=%s', $out);

        // Add the chosen proto files to generate
        foreach ($protos as $proto) {
            $commandLine .= " ".$proto;
        }

        $builder = new Process($commandLine);

        return $builder;
    }

    /**
     * @param array  $files
     * @param bool   $isFile
     *
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    protected function getRealPaths(array $files, $isFile = false)
    {
        $paths = [];

        foreach ($files as $file) {
            $paths[] = $this->getRealPath($file, $isFile);
        }

        return $paths;
    }

    /**
     * @param string|SplFileInfo $file
     * @param bool               $isFile
     *
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    protected function getRealPath($file, $isFile = false)
    {
        if ( ! $file instanceof SplFileInfo) {
            $file = new SplFileInfo($file);
        }

        $realpath = $file->getRealPath();

        if (false === $realpath) {
            throw new InvalidArgumentException(sprintf(
                'The %s "%s" does not exist.',
                ($isFile ? 'file' : 'directory'),
                $file->getPathname()
            ));
        }

        return $realpath;
    }

    /**
     * @return string
     *
     * @throws \RuntimeException
     */
    protected function findDescriptorsPath()
    {
        foreach ($this->descriptorPaths as $path) {
            if ( ! is_dir($path)) {
                continue;
            }

            return realpath($path) ?: $path;
        }

        throw new RuntimeException('Unable to find "protobuf-php/google-protobuf-proto".');
    }

    /**
     * @return \Symfony\Component\Process\Process
     */
    public function createProtocVersionProcess()
    {
        $protoc  = $this->protoc;
        $process = new Process("$protoc --version");

        return $process;
    }
}
