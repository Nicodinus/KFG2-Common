<?php


namespace Nicodinus\KFG2\Common\Support;


trait GracefulShutdownTrait
{
    /** @var bool */
    private bool $isShutdownPending = false;

    /**
     * @param int $code
     *
     * @return void
     */
    public function shutdown(int $code): void
    {
        if ($this->isShutdownPending() === true) {
            return;
        }

        $this->isShutdownPending = true;
    }

    /**
     * @return bool
     */
    public function isShutdownPending(): bool
    {
        return $this->isShutdownPending;
    }
}