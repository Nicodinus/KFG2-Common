<?php


namespace Nicodinus\KFG2\Common\Application\Providers;


use Amp\Failure;
use Amp\Promise;
use Nicodinus\KFG2\Common\Application\ApplicationInterface;
use Nicodinus\KFG2\Common\Application\Providers\Errors\InvalidProvider;
use Nicodinus\KFG2\Common\Application\Providers\Errors\ProviderAlreadyLoaded;
use Nicodinus\KFG2\Common\Application\Providers\Errors\ProviderAlreadyRegistered;
use Nicodinus\KFG2\Common\Application\Providers\Errors\ProviderNotFound;
use Nicodinus\KFG2\Common\Application\Providers\Errors\ProviderNotLoaded;
use Nicodinus\KFG2\Common\Support\Utils;
use Throwable;
use function Amp\call;

class AppProviders implements AppProvidersInterface
{
    /** @var string[] */
    private array $classnamesRegistry;
    
    /** @var AppProviderInterface[]|object[] */
    private array $registry;

    /** @var ApplicationInterface */
    private ApplicationInterface $application;

    //

    /**
     * Providers constructor.
     * @param ApplicationInterface $application
     */
    public function __construct(ApplicationInterface $application)
    {
        $this->application = $application;

        $this->classnamesRegistry = [];
        $this->registry = [];
    }

    /**
     * @param string $classname
     *
     * @return bool
     */
    public function isExists(string $classname): bool
    {
        return $this->__findClassname($classname) !== false;
    }

    /**
     * @param string $classname
     *
     * @throws ProviderNotFound
     *
     * @return bool
     */
    public function isLoaded(string $classname): bool
    {
        if (!$this->isExists($classname)) {
            throw ProviderNotFound::factory($classname);
        }

        return isset($this->registry[$classname]);
    }

    /**
     * @return array
     */
    public function getAll(): array
    {
        return $this->registry;
    }

    /**
     * @param string $classname
     * 
     * @throws ProviderNotLoaded
     * 
     * @return AppProviderInterface|object
     */
    public function get(string $classname)
    {
        if (!$this->isLoaded($classname)) {
            throw ProviderNotLoaded::factory($classname);
        }
        
        return $this->registry[$classname];
    }

    /**
     * @param string $classname
     *
     * @throws InvalidProvider|ProviderAlreadyRegistered
     *
     * @return static
     */
    public function register(string $classname)
    {
        if (!Utils::isImplementClassname($classname, AppProviderInterface::class)) {
            throw InvalidProvider::factory($classname);
        }

        if (!$this->isExists($classname)) {
            throw ProviderAlreadyRegistered::factory($classname);
        }

        $this->__addClassname($classname);

        return $this;
    }

    /**
     * @param string $classname
     *
     * @throws InvalidProvider|ProviderAlreadyRegistered
     *
     * @return static
     */
    public function unregister(string $classname)
    {
        if (!$this->isExists($classname)) {
            throw ProviderNotFound::factory($classname);
        }

        if ($this->isLoaded($classname)) {
            throw ProviderAlreadyLoaded::factory($classname);
        }

        $this->__removeClassname($classname);

        return $this;
    }

    /**
     * @param string|AppProviderInterface $classname
     *
     * @throws ProviderNotFound|ProviderAlreadyLoaded
     *
     * @return Promise<AppProviderInterface|object>|Failure<Throwable>
     */
    public function load(string $classname): Promise
    {
        if (!$this->isExists($classname)) {
            throw ProviderNotFound::factory($classname);
        }

        if ($this->isLoaded($classname)) {
            throw ProviderAlreadyLoaded::factory($classname);
        }

        return call(static function (self &$self) use ($classname) {

            try {

                /** @var AppProviderInterface $provider */
                $provider = new $classname();
                yield $provider->load($self->application);

                $self->registry[$classname] = $provider;

                return $provider;

            } catch (Throwable $e) {
                return new Failure($e);
            }

        }, $this);
    }

    /**
     * @param string|AppProviderInterface $classname
     *
     * @throws ProviderNotFound|ProviderNotLoaded
     *
     * @return Promise<void>|Failure<Throwable>
     */
    public function unload(string $classname): Promise
    {
        if (!$this->isExists($classname)) {
            throw ProviderNotFound::factory($classname);
        }

        if (!$this->isLoaded($classname)) {
            throw ProviderNotLoaded::factory($classname);
        }

        return call(static function (self &$self) use ($classname) {

            try {

                yield $self->registry[$classname]->unload();
                unset($self->registry[$classname]);

            } catch (Throwable $e) {
                return new Failure($e);
            }

        }, $this);
    }

    /**
     * @param string|AppProviderInterface $classname
     *
     * @throws ProviderNotFound
     *
     * @return Promise<AppProviderInterface|object>|Failure<Throwable>
     */
    public function reload(string $classname): Promise
    {
        if (!$this->isExists($classname)) {
            throw ProviderNotFound::factory($classname);
        }

        return call(static function (self &$self) use ($classname) {

            try {

                if ($self->isLoaded($classname)) {
                    yield $self->unload($classname);
                }

                yield $self->load($classname);

            } catch (Throwable $e) {
                return new Failure($e);
            }

        }, $this);
    }

    /**
     * @param array $classnames
     *
     * @return static
     *
     * @throws \ReflectionException
     */
    public function locate(array $classnames): self
    {
        foreach ($classnames as $classname) {

            if (!Utils::isImplementClassname($classname, AppProviderInterface::class)) {
                continue;
            }

            if ($this->isExists($classname)) {
                continue;
            }

            $testClass = new \ReflectionClass($classname);
            if ($testClass->isAbstract()) {
                continue;
            }

            $this->__addClassname($classname);
        }

        return $this;
    }

    /**
     * @return Promise<AppProviderInterface[]|object[]>|Failure<Throwable>
     */
    public function loadAll(): Promise
    {
        return call(static function (self &$self) {

            try {

                $loaded = [];

                foreach ($self->classnamesRegistry as $classname) {

                    if ($self->isLoaded($classname)) {
                        continue;
                    }

                    yield $self->load($classname);

                }

                return $loaded;

            } catch (Throwable $e) {
                return new Failure($e);
            }

        }, $this);
    }

    /**
     * @return Promise<void>|Failure<Throwable>
     */
    public function unloadAll(): Promise
    {
        return call(static function (self &$self) {

            try {

                foreach ($self->registry as $provider) {
                    yield $provider->unload();
                }

                $self->registry = [];

            } catch (Throwable $e) {
                return new Failure($e);
            }

        }, $this);
    }

    /**
     * @return Promise<AppProviderInterface[]|object[]>|Failure<Throwable>
     */
    public function reloadAll(): Promise
    {
        return call(static function (self &$self) {

            try {

                yield $self->unloadAll();
                return yield $self->loadAll();

            } catch (Throwable $e) {
                return new Failure($e);
            }

        }, $this);
    }

    //

    /**
     * @param string $classname
     *
     * @return void
     */
    private function __addClassname(string $classname)
    {
        $this->classnamesRegistry[] = $classname;
    }

    /**
     * @param string $classname
     *
     * @return false|int|string
     */
    private function __findClassname(string $classname)
    {
        return array_search($classname, $this->classnamesRegistry);
    }

    /**
     * @param string $classname
     *
     * @return void
     */
    private function __removeClassname(string $classname)
    {
        $find = $this->__findClassname($classname);

        $newArr = [];
        foreach ($this->classnamesRegistry as $key => $value) {
            if ($find != $key) {
                $newArr[] = $value;
            }
        }

        $this->classnamesRegistry = $newArr;
    }
}