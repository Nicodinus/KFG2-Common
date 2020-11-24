<?php


namespace Nicodinus\KFG2\Common\Application\Providers;


use Amp\Failure;
use Amp\Promise;
use Amp\Success;
use Nicodinus\KFG2\Common\Application\ApplicationInterface;
use Throwable;


trait AppProviderTrait
{
    /** @var ApplicationInterface|null */
    protected ?ApplicationInterface $app;

    /**
     * @param ApplicationInterface $app
     *
     * @return Promise<void>|Failure<\Throwable>
     */
    public function load(ApplicationInterface $app): Promise
    {
        $this->app = $app;
        return new Success();
    }

    /**
     * @return Promise<void>|Failure<Throwable>
     */
    public function unload(): Promise
    {
        $this->app = null;
        return new Success();
    }
}