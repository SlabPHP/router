<?php
/**
 * Base route pattern validator class, extend this
 *
 * @author Eric
 * @package Slab
 * @subpackage Router
 */
namespace Slab\Router\Validators;

abstract class Base
{
    /**
     * If you'd like to save stuff to the output
     *
     * @var array
     */
    protected $output = [];

    /**
     * If the value changes, this is the output value
     *
     * @var mixed
     */
    protected $outputValue = null;

    /**
     * Validate this particular segment and returns the value if true
     *
     * @param string $segment
     * @return boolean
     */
    abstract public function validate($segment);

    /**
     * Returns a value if the value changed during validation
     *
     * @return mixed
     */
    public function didValueChange()
    {
        return $this->outputValue;
    }

    /**
     * Pass output to controller
     *
     * @param $name
     * @param $value
     */
    protected function passOutput($name, $value)
    {
        $this->output[$name] = $value;
    }

    /**
     * Get output of validation
     *
     * @return array
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * Test URL against the current routed url
     *
     * @param $input
     */
    protected function testURL($input)
    {
        if (empty($input)) return false;

        if ($input[0] == '/' && ($input == $this->getSystem()->router->currentRequest)) {
            return true;
        } else if (substr($input, 0, 4) == 'http' && ($input == $this->getSystem()->router->currentHREF)) {
            return true;
        }

        return false;
    }

}