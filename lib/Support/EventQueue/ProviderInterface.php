<?php


namespace Nicodinus\KFG2\Common\Support\EventQueue;


use Amp\Failure;
use Amp\Promise;
use Nicodinus\KFG2\Common\Support\AlreadyReleasedInstanceError;
use Nicodinus\KFG2\Common\Support\CanBeReleasedInstanceInterface;

interface ProviderInterface extends CanBeReleasedInstanceInterface
{
    /**
     * @param string $channel
     *
     * @return SupplierInterface
     *
     * @throws AlreadyReleasedInstanceError
     */
    public function getSupplier(string $channel): SupplierInterface;

    /**
     * @param object $item
     * @param string $channel
     *
     * @return Promise<int>|Failure<\Throwable>
     */
    public function queue(object $item, string $channel): Promise;
}