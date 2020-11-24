<?php


namespace Nicodinus\KFG2\Common\Application\Providers\Errors;


/**
 * Class ProviderNotFound
 * @package Nicodinus\KFG2\Common\Application\Providers\Errors
 */
class ProviderNotFound extends \Error
{
    /**
     * @param string $classname
     * @param string $message
     *
     * @return static
     */
    public static function factory(string $classname, string $message = "Can't find provider %s at repository!")
    {
        $message = sprintf($message, $classname);

        return new static($message);
    }
}