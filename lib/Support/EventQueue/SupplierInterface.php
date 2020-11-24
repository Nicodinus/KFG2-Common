<?php


namespace Nicodinus\KFG2\Common\Support\EventQueue;


use Amp\Failure;
use Amp\Promise;
use Nicodinus\KFG2\Common\Support\CanBeReleasedInstanceInterface;

interface SupplierInterface extends CanBeReleasedInstanceInterface
{
    /**
     * @return Promise<object>|Failure<\Throwable>
     */
    public function await(): Promise;

    /**
     * @return string
     */
    public function getChannel(): string;
}