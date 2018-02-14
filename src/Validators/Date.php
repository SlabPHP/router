<?php
/**
 * Numeric route pattern validator class
 *
 * @author Eric
 * @package Slab
 * @subpackage Router
 */
namespace Slab\Router\Validators;

class Date extends Any
{
    /**
     * Validate this particular segment and returns the value if true
     *
     * @param string $segment
     * @return boolean
     */
    public function validate($segment)
    {
        //Slashes don't come in by segment so if a date has dashes, lets make them slashes
        $segment = str_replace('-', '/', $segment);

        try {
            $this->outputValue = new \DateTime($segment);
        } catch (\Exception $exception) {
            //Do nothing, this is a fail
            return false;
        }

        return ($this->outputValue->format('U') != 0);
    }
}