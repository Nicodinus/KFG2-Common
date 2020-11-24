<?php


namespace Nicodinus\KFG2\Common\Application\Providers\Errors;


/**
 * Class ProviderAlreadyRegistered
 * @package Nicodinus\KFG2\Common\Application\Providers\Errors
 */
class ProviderAlreadyRegistered extends \Error
{
    /**
     * @param string $classname
     * @param string $message
     * 
     * @return static
     */
    public static function factory(string $classname, string $message = "Provider %s is already registered!")
    {
        $message = sprintf($message, $classname);

        return new static($message);
    }
}