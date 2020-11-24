<?php


namespace Nicodinus\KFG2\Common\Support\Enums;


use MyCLabs\Enum\Enum;

/**
 * Class EventEnum
 * @package Konfigurator\Common\Enums
 * @method static static EMPTY()
 */
class EventEnum extends Enum
{
    public const EMPTY = 'empty';

    /** @var mixed */
    protected $eventData;


    /**
     * EventEnum constructor.
     * @param $value
     * @param mixed $eventData
     */
    public function __construct($value, $eventData = null)
    {
        parent::__construct($value);

        $this->eventData = $eventData;
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return static
     */
    public static function __callStatic($name, $arguments)
    {
        $array = static::toArray();
        if (isset($array[$name]) || \array_key_exists($name, $array)) {
            return new static($array[$name], ...$arguments);
        }

        throw new \BadMethodCallException("No static method or enum constant '$name' in class " . static::class);
    }

    /**
     * @return mixed
     */
    public function getEventData()
    {
        return $this->eventData;
    }
}