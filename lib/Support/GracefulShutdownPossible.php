<?php


namespace Nicodinus\KFG2\Common\Support;


interface GracefulShutdownPossible
{
    /**
     * @param int $code
     *
     * @return void
     */
    public function shutdown(int $code): void;

    /**
     * @return bool
     */
    public function isShutdownPending(): bool;
}