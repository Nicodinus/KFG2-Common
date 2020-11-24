<?php


namespace Nicodinus\KFG2\Common\Application;


use Amp\File\Driver;
use Amp\Promise;
use Composer\Autoload\ClassLoader;
use Nicodinus\KFG2\Common\Application\Providers\AppProvidersInterface;
use Nicodinus\KFG2\Common\Support\CanBeReleasedInstanceInterface;
use Nicodinus\KFG2\Common\Support\GracefulShutdownPossible;
use Psr\Log\LoggerInterface;

interface ApplicationInterface extends GracefulShutdownPossible, CanBeReleasedInstanceInterface
{
    /**
     * @return string
     */
    public function getAppName(): string;

    /**
     * @return string
     */
    public function getAppDirectory(): string;

    /**
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface;

    /**
     * @return AppProvidersInterface
     */
    public function providers(): AppProvidersInterface;

    /**
     * @param string|null $key
     * @return array
     */
    public function getEnv(?string $key = null): array;

    /**
     * @return ClassLoader
     */
    public function getClassloader(): ClassLoader;

    /**
     * @return Driver
     */
    public function getFilesystem(): Driver;

    /**
     * @param Driver $fileSystem
     *
     * @return static
     */
    public function setFilesystem(Driver $fileSystem);

    /**
     * @return Promise<void>
     */
    public function init(): Promise;

    /**
     * @return Promise<void>
     */
    public function reset(): Promise;
    
    /**
     * @return Promise<void>
     */
    public function run(): Promise;
}