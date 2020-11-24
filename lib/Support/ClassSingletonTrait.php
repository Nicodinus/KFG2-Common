<?php


namespace Nicodinus\KFG2\Common\Support;


trait ClassSingletonTrait
{
    /** @var static */
    private static self $_INSTANCE;

    /**
     * @param mixed ...$args
     * @return static
     */
    public static function getInstance(...$args)
    {
        if (empty(static::$_INSTANCE)) {
            static::createInstance(...$args);
        }

        return static::$_INSTANCE;
    }

    /**
     * @param mixed ...$args
     * @return static
     */
    public static function createInstance(...$args)
    {
        if (!empty(static::$_INSTANCE)) {
            throw new \LogicException("Cannot create another instance of this class!");
        }

        return static::$_INSTANCE = new static(...$args);
    }
}