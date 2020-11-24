<?php


namespace Nicodinus\KFG2\Common\Support\Enums;


use MyCLabs\Enum\Enum;

/**
 * Class StateEnum
 * @package Konfigurator\Common\Enums
 * @method static static UNDEFINED()
 */
class StateEnum extends Enum
{
    public const UNDEFINED = 'undefined';

    /** @var static[] */
    private static array $cacheEnums = [];

    /**
     * @param string $name
     * @param array $arguments
     * @return static
     */
    public static function __callStatic($name, $arguments)
    {
        $array = static::toArray();
        if (isset($array[$name]) || \array_key_exists($name, $array)) {
            if (!isset(static::$cacheEnums[$array[$name]])) {
                static::$cacheEnums[$array[$name]] = new static($array[$name], ...$arguments);
            }
            return static::$cacheEnums[$array[$name]];
        }

        throw new \BadMethodCallException("No static method or enum constant '$name' in class " . static::class);
    }
}