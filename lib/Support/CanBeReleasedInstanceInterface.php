<?php


namespace Nicodinus\KFG2\Common\Support;


interface CanBeReleasedInstanceInterface
{
    /**
     * @return bool
     */
    public function isReleased(): bool;

    /**
     * @return void
     */
    public function release(): void;
}