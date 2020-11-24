<?php


namespace Nicodinus\KFG2\Common\Application\Providers;


use Amp\Failure;
use Amp\Promise;
use Nicodinus\KFG2\Common\Application\Providers\Errors\InvalidProvider;
use Nicodinus\KFG2\Common\Application\Providers\Errors\ProviderAlreadyLoaded;
use Nicodinus\KFG2\Common\Application\Providers\Errors\ProviderAlreadyRegistered;
use Nicodinus\KFG2\Common\Application\Providers\Errors\ProviderNotFound;
use Nicodinus\KFG2\Common\Application\Providers\Errors\ProviderNotLoaded;
use Throwable;

interface AppProvidersInterface
{
    /**
     * @param string $classname
     *
     * @return bool
     */
    public function isExists(string $classname): bool;

    /**
     * @param string $classname
     *
     * @throws ProviderNotFound
     *
     * @return bool
     */
    public function isLoaded(string $classname): bool;

    /**
     * @return array
     */
    public function getAll(): array;

    /**
     * @param string $classname
     *
     * @throws ProviderNotLoaded
     *
     * @return AppProviderInterface|object
     */
    public function get(string $classname);

    /**
     * @param string $classname
     *
     * @throws InvalidProvider|ProviderAlreadyRegistered
     *
     * @return static
     */
    public function register(string $classname);

    /**
     * @param string $classname
     *
     * @throws InvalidProvider|ProviderAlreadyRegistered
     *
     * @return static
     */
    public function unregister(string $classname);

    /**
     * @param string|AppProviderInterface $classname
     *
     * @throws ProviderNotFound|ProviderAlreadyLoaded
     *
     * @return Promise<AppProviderInterface|object>|Failure<Throwable>
     */
    public function load(string $classname): Promise;

    /**
     * @param string|AppProviderInterface $classname
     *
     * @throws ProviderNotFound|ProviderNotLoaded
     *
     * @return Promise<void>|Failure<Throwable>
     */
    public function unload(string $classname): Promise;

    /**
     * @param string|AppProviderInterface $classname
     *
     * @throws ProviderNotFound
     *
     * @return Promise<AppProviderInterface|object>|Failure<Throwable>
     */
    public function reload(string $classname): Promise;

    /**
     * @param array $classnames
     *
     * @return static
     *
     * @throws \ReflectionException
     */
    public function locate(array $classnames): self;

    /**
     * @return Promise<AppProviderInterface[]|object[]>|Failure<Throwable>
     */
    public function loadAll(): Promise;

    /**
     * @return Promise<void>|Failure<Throwable>
     */
    public function unloadAll(): Promise;

    /**
     * @return Promise<AppProviderInterface[]|object[]>|Failure<Throwable>
     */
    public function reloadAll(): Promise;


}