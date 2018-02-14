<?php
/**
 * SlabPHP Router Parameter
 *
 * @package Slab
 * @subpackage Router
 * @author Eric
 */
namespace Slab\Router;

class Parameter
{
    /**
     * @var array
     */
    private $attributes = [];

    /**
     * @var string
     */
    private $value;

    /**
     * @var string
     */
    private $name;

    /**
     * Create a parameter
     *
     * @param $name
     * @param $value
     */
    public function __construct($name, $value, $attributes = [])
    {
        $this->name = $name;
        $this->value = $value;
        $this->attributes = $attributes;
    }

    /**
     * Get value
     *
     * @return mixed
     */
    public function __toString()
    {
        return $this->value;
    }

    /**
     * Get attributes
     *
     * @param null $attribute
     * @return array
     */
    public function getAttribute($attribute)
    {
        if (isset($this->attributes[$attribute])) {
            return $this->attributes[$attribute];
        }

        return null;
    }

    /**
     * Get all attributes
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }
}