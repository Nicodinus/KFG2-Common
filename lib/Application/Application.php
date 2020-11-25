<?php


namespace Nicodinus\KFG2\Common\Application;


use Amp\CancellationTokenSource;
use Amp\File\Driver;
use Amp\Log\StreamHandler;
use Amp\Loop;
use Amp\Parallel\Worker\DefaultPool;
use Amp\Parallel\Worker\Pool;
use Amp\Promise;
use Amp\Success;
use Composer\Autoload\ClassLoader;
use Exception;
use HaydenPierce\ClassFinder\ClassFinder;
use Monolog\Formatter\LineFormatter;
use Monolog\Logger;
use Nicodinus\KFG2\Common\Application\Providers\AppProviders;
use Nicodinus\KFG2\Common\Application\Providers\AppProvidersInterface;
use Nicodinus\KFG2\Common\Support\ClassSingletonTrait;
use Nicodinus\KFG2\Common\Support\Dotenv\Dotenv;
use Nicodinus\KFG2\Common\Support\GracefulShutdownTrait;
use Nicodinus\KFG2\Common\Support\PendingShutdownError;
use Nicodinus\KFG2\Common\Support\Utils;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use Throwable;
use function Amp\asyncCall;
use function Amp\ByteStream\getStdout;
use function Amp\call;
use function Amp\delay;
use function Amp\File\filesystem;

class Application implements ApplicationInterface
{
    use GracefulShutdownTrait, ClassSingletonTrait;

    //

    /** @var int */
    private int $appHookedTimeout = 3;

    /** @var int */
    private int $appHookedTimeoutMax = 13;

    //

    /** @var AppProviders|AppProvidersInterface */
    private AppProvidersInterface $providers;

    /** @var DefaultPool|Pool|null */
    private ?Pool $workerPool;

    /** @var LoggerInterface|Logger */
    private $logger;

    /** @var string */
    private string $appDirectory;

    /** @var string */
    private string $appName;

    /** @var bool */
    private bool $isAppInitialized;

    /** @var CancellationTokenSource|null */
    private ?CancellationTokenSource $cancellationTokenSource;

    /** @var ClassLoader */
    private ClassLoader $classLoader;

    /** @var Driver */
    private Driver $fileSystem;

    //

    /**
     * Application constructor.
     * @param string $appName
     * @param string $appDirectory
     */
    private function __construct(string $appName, string $appDirectory)
    {
        $this->appDirectory = $appDirectory;
        $this->appName = $appName;

        $this->providers = new AppProviders($this);
        $this->workerPool = null;
        $this->isAppInitialized = false;
        $this->cancellationTokenSource = null;

        $this->logger = new NullLogger();

        $this->classLoader = $this->_locateClassloader();

        $this->fileSystem = filesystem();
    }

    /**
     * @return string
     */
    public function getAppName(): string
    {
        return $this->appName;
    }

    /**
     * @return string
     */
    public function getAppDirectory(): string
    {
        return $this->appDirectory;
    }

    /**
     * @return Logger|LoggerInterface|NullLogger
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * @return AppProvidersInterface|AppProviders
     */
    public function providers(): AppProvidersInterface
    {
        return $this->providers;
    }

    /**
     * @param string|null $key
     * @return array
     */
    public function getEnv(?string $key = null): array
    {
        if ($key === null) {
            return $_ENV;
        } else {
            return $_ENV[$key] ?? null;
        }
    }

    /**
     * @return Driver
     */
    public function getFilesystem(): Driver
    {
        return $this->fileSystem;
    }

    /**
     * @param Driver $fileSystem
     *
     * @return static
     */
    public function setFilesystem(Driver $fileSystem)
    {
        $this->fileSystem = $fileSystem;
        return $this;
    }

    /**
     * @return Promise<void>
     */
    public function init(): Promise
    {
        if ($this->isShutdownPending()) {
            throw new PendingShutdownError();
        }

        if ($this->isAppInitialized) {
            return new Success();
        }

        $this->workerPool = null;
        $this->isAppInitialized = false;

        $self = &$this;
        Loop::setErrorHandler(static function (Throwable $exception) use (&$self) {
            $self->errorHandler($exception);
        });

        return call(static function (self &$self) {

            $stdout = getStdout();

            $handler = (new StreamHandler($stdout))
                ->setFormatter(new LineFormatter(null, "d.m.Y - H:i:s.u", false, true))
                ->setLevel(getenv('DEBUG_LEVEL') ?: 'info')
            ;

            $self->logger = new Logger($self->getAppName(), [
                $handler,
            ]);

            $self->getLogger()->info(__CLASS__);

            $self->getLogger()->debug("Initialize app...");

            $self->workerPool = new DefaultPool();

            yield $self->loadEnv();

            //
            $classFinder = new \Nicodinus\KFG2\Common\Support\ClassFinder\ClassFinder($self);
            $classnames = yield $classFinder->findClassnames('Nicodinus\KFG2\Common\Application\Providers', ClassFinder::RECURSIVE_MODE);

            $self->providers()
                ->locate(get_declared_classes())
                ->locate($classnames)
            ;

            foreach ($self->_getProvidersNamespaces() as $namespace) {
                $classnames = yield $classFinder->findClassnames($namespace, ClassFinder::RECURSIVE_MODE);
                $self->providers()->locate($classnames);
            }

            $self->isAppInitialized = true;

            $self->getLogger()->debug("App initialized!");

        }, $this);
    }

    /**
     * @return Promise<void>
     */
    public function reset(): Promise
    {
        if ($this->isShutdownPending()) {
            throw new PendingShutdownError();
        }

        return call(static function (self &$self) {

            $self->getLogger()->info("Resetting app...");

            if ($self->isAppInitialized) {

                $self->cancellationTokenSource->cancel();
                $self->cancellationTokenSource = null;

                yield $self->providers()->unloadAll();

                yield $self->workerPool->shutdown();

                $self->getLogger()->close();

                $self->isAppInitialized = false;

            }

            yield $self->init();

        }, $this);
    }

    /**
     * @return Promise<void>
     */
    public function run(): Promise
    {
        if ($this->isShutdownPending()) {
            throw new PendingShutdownError();
        }

        return call(static function (self &$self) {

            if (!$self->isAppInitialized) {
                yield $self->init();
            }

            if (!empty($self->cancellationTokenSource)) {
                return;
            }

            yield $self->providers()->loadAll();

            $promises = [];
            $self->cancellationTokenSource = new CancellationTokenSource();

            foreach ($self->providers()->getAll() as $provider) {
                $promises[] = $provider->run($self->cancellationTokenSource->getToken());
            }

            if (sizeof($promises) == 0) {
                $self->getLogger()->warning("There are no app providers has been loaded!");
            }

            asyncCall(static function (self $self) use ($promises) {

                yield Promise\all($promises);

                $self->shutdown();

            }, $self);

        }, $this);
    }

    /**
     * @param int $code
     *
     * @return void
     */
    public function shutdown(int $code = 0): void
    {
        if ($this->isShutdownPending()) {
            return;
        }
        $this->isShutdownPending = true;

        if (!empty($this->cancellationTokenSource)) {
            $this->cancellationTokenSource->cancel();
            $this->cancellationTokenSource = null;
        }

        $this->getLogger()->info("Shutting down pending...");

        asyncCall(static function (self &$self) use ($code) {

            $alarmFlag = 0;

            $ts = microtime(true);
            while (true) {

                $info = Loop::getInfo();
                if ($info['enabled_watchers']['referenced'] == 0) {
                    if ($alarmFlag > 0) {
                        $self->getLogger()->warning("Closed normally! Please check what locks application shutdown.");
                    } else {
                        $self->getLogger()->info("Closed!");
                    }
                    exit($code);
                    break;
                }

                switch ($alarmFlag) {
                    case 0:
                        if (microtime(true) - $ts > $self->appHookedTimeout) {
                            $tt = $self->appHookedTimeoutMax - $self->appHookedTimeout;
                            $self->getLogger()->warning("Something hooked application! Application will be closed in {$tt} seconds.");
                            $alarmFlag += 1;
                        }
                        break;
                    case 1:
                        if (microtime(true) - $ts > $self->appHookedTimeoutMax) {
                            $self->getLogger()->warning("Terminate app pending...");
                            $alarmFlag += 1;
                        }
                        break;
                    case 2:
                        $self->getLogger()->error("Closed by killing! Please check what locks application shutdown.");
                        exit($code === 0 ? -1 : $code);
                }

                yield delay(100);
            }

        }, $this);
    }

    /**
     * @return bool
     */
    public function isReleased(): bool
    {
        return $this->isShutdownPending();
    }

    /**
     * @return void
     */
    public function release(): void
    {
        $this->shutdown();
    }

    /**
     * @return ClassLoader
     */
    public function getClassloader(): ClassLoader
    {
        return $this->classLoader;
    }

    /**
     * @return mixed
     */
    private function _locateClassloader()
    {
        foreach ([
                     $this->getAppDirectory() . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php',
                     dirname(dirname(dirname($this->getAppDirectory()))) . DIRECTORY_SEPARATOR . 'autoload.php',
                 ] as $path) {
            if (file_exists($path)) {
                return require $path;
            }
        }

        throw new RuntimeException('Unable to locate class loader');
    }

    /**
     * @param Throwable $exception
     *
     * @return void
     */
    protected function errorHandler(Throwable $exception): void
    {
        $this->getLogger()->error("An unhandled exception!", [
            'exception' => $exception,
        ]);

        if (Utils::isExtendsClassname($exception, Exception::class)) {
            $this->shutdown($exception->getCode());
        }
    }

    /**
     * @return Promise
     */
    protected function loadDefaultEnv(): Promise
    {
        return new Success();
    }

    /**
     * @return Promise<void>
     */
    protected function loadEnv(): Promise
    {
        return call(static function (self &$self) {

            $dotenv = Dotenv::createUnsafeImmutable($self->getAppDirectory());

            try {
                yield $dotenv->load();
            } catch (Throwable $e) {
                $self->getLogger()->warning("There is no .env file found. Using default env configuration!");
                yield $self->loadDefaultEnv();
            }

        }, $this);
    }

    /**
     * @return string[]
     */
    protected function _getProvidersNamespaces(): array
    {
        return [];
    }
}