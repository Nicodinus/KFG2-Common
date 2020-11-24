<?php


namespace Nicodinus\KFG2\Common\Support;


class AlreadyReleasedInstanceError extends \Error
{
    public function __construct(
        string $message = 'This instance already released!',
        int $code = 0,
        \Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}