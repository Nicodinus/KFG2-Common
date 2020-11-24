<?php


namespace Nicodinus\KFG2\Common\Application\Providers\Errors;


/**
 * Class InvalidProvider
 * @package Nicodinus\KFG2\Common\Application\Providers\Errors
 */
class InvalidProvider extends \Error
{
    /**
     * @param string $classname
     * @param string $message
     *
     * @return static
     */
    public static function factory(string $classname, string $message = "Invalid provider %s!")
    {
        $message = sprintf($message, $classname);

        return new static($message);
    }
}