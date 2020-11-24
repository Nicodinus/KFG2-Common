<?php


namespace Nicodinus\KFG2\Common\Support;


use HaydenPierce\ClassFinder\ClassFinder;
use Nicodinus\KFG2\Common\Application\Providers\AppProviderInterface;

class Utils
{
    /**
     * @param object|string $target
     * @param string $interface
     * @return bool
     */
    public static function isImplementClassname($target, string $interface): bool
    {
        return array_search($interface, class_implements($target)) !== false;
    }

    /**
     * @param object|string $target
     * @param string|object $classname
     * @return bool
     */
    public static function isExtendsClassname($target, $classname): bool
    {
        return $target == $classname
            || is_a($target, $classname)
            || is_subclass_of($target, $classname)
            || (is_object($target) && $target instanceof $classname)
        ;
    }
}