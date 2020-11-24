<?php


namespace Nicodinus\KFG2\Common\Application\Providers\Errors;


/**
 * Class ProviderAlreadyLoaded
 * @package Nicodinus\KFG2\Common\Application\Providers\Errors
 */
class ProviderAlreadyLoaded extends \Error
{
    /**
     * @param string $classname
     * @param string $message
     * 
     * @return static
     */
    public static function factory(string $classname, string $message = "Provider %s is already loaded!")
    {
        $message = sprintf($message, $classname);

        return new static($message);
    }
}