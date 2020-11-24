<?php


namespace Nicodinus\KFG2\Common\Application\Providers;


use Amp\CancellationToken;
use Amp\Failure;
use Amp\Promise;
use Nicodinus\KFG2\Common\Application\ApplicationInterface;
use Throwable;


interface AppProviderInterface
{
    /**
     * @param ApplicationInterface $app
     *
     * @return Promise<void>|Failure<Throwable>
     */
    public function load(ApplicationInterface $app): Promise;

    /**
     * @param CancellationToken $cancellationToken
     *
     * @return Promise<void>|Failure<Throwable>
     */
    public function run(CancellationToken $cancellationToken): Promise;

    /**
     * @return Promise<void>|Failure<Throwable>
     */
    public function unload(): Promise;
}