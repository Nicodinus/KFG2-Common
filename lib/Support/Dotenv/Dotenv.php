<?php


namespace Nicodinus\KFG2\Common\Support\Dotenv;


use Amp\Failure;
use Amp\Promise;
use Dotenv\Loader\Loader;
use Dotenv\Loader\LoaderInterface;
use Dotenv\Parser\Parser;
use Dotenv\Parser\ParserInterface;
use Dotenv\Repository\Adapter\ArrayAdapter;
use Dotenv\Repository\RepositoryBuilder;
use Dotenv\Repository\RepositoryInterface;
use Dotenv\Store\StoreInterface;
use Dotenv\Store\StringStore;
use function Amp\call;


class Dotenv extends \Dotenv\Dotenv
{
    /** @var ParserInterface */
    private ParserInterface $parser;

    /** @var StoreInterface */
    private StoreInterface $store;

    /** @var LoaderInterface */
    private LoaderInterface $loader;

    /** @var RepositoryInterface */
    private RepositoryInterface $repository;

    /**
     * Create a new dotenv instance.
     *
     * @param \Dotenv\Store\StoreInterface           $store
     * @param \Dotenv\Parser\ParserInterface         $parser
     * @param \Dotenv\Loader\LoaderInterface         $loader
     * @param \Dotenv\Repository\RepositoryInterface $repository
     *
     * @return void
     */
    public function __construct(
        StoreInterface $store,
        ParserInterface $parser,
        LoaderInterface $loader,
        RepositoryInterface $repository
    ) {
        $this->store = $store;
        $this->parser = $parser;
        $this->loader = $loader;
        $this->repository = $repository;

        parent::__construct($store, $parser, $loader, $repository);
    }

    /**
     * @return string|StoreBuilder
     */
    protected static function getStoreBuilderClass(): string
    {
        return StoreBuilder::class;
    }

    /**
     * Create a new dotenv instance.
     *
     * @param \Dotenv\Repository\RepositoryInterface $repository
     * @param string|string[]                        $paths
     * @param string|string[]|null                   $names
     * @param bool                                   $shortCircuit
     * @param string|null                            $fileEncoding
     *
     * @return \Dotenv\Dotenv
     */
    public static function create(RepositoryInterface $repository, $paths, $names = null, bool $shortCircuit = true, string $fileEncoding = null)
    {
        $storeBuilder = static::getStoreBuilderClass();

        $builder = $names === null ? $storeBuilder::createWithDefaultName() : $storeBuilder::createWithNoNames();

        foreach ((array) $paths as $path) {
            $builder = $builder->addPath($path);
        }

        foreach ((array) $names as $name) {
            $builder = $builder->addName($name);
        }

        if ($shortCircuit) {
            $builder = $builder->shortCircuit();
        }

        return new self($builder->fileEncoding($fileEncoding)->make(), new Parser(), new Loader(), $repository);
    }

    /**
     * Parse the given content and resolve nested variables.
     *
     * This method behaves just like load(), only without mutating your actual
     * environment. We do this by using an array backed repository.
     *
     * @param string $content
     *
     * @throws \Dotenv\Exception\InvalidFileException
     *
     * @return Promise<array<string,string|null>>|Failure<\Throwable>
     */
    public static function parse(string $content): Promise
    {
        return call(static function () use ($content) {

            try {

                $repository = RepositoryBuilder::createWithNoAdapters()->addAdapter(ArrayAdapter::class)->make();

                $phpdotenv = new self(new StringStore($content), new Parser(), new Loader(), $repository);

                return yield $phpdotenv->load();

            } catch (\Throwable $e) {
                return new Failure($e);
            }

        });
    }

    /**
     * @return Promise<array>|Failure<\Throwable>
     */
    public function load()
    {
        return call(static function (self &$self) {

            try {

                $data = yield $self->store->read();

                $entries = $self->parser->parse($data);

                return $self->loader->load($self->repository, $entries);

            } catch (\Throwable $e) {
                return new Failure($e);
            }

        }, $this);
    }
}