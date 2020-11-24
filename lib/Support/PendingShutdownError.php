<?php


namespace Nicodinus\KFG2\Common\Support;


final class PendingShutdownError extends \Error
{
    public function __construct(
        string $message = 'Application is going shutdown now!',
        int $code = 0,
        \Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}