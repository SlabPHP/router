<?php
/**
 * Value route pattern validator class, segment must match the pattern exactly
 *
 * @author Eric
 * @package Slab
 * @subpackage Router
 */
namespace Slab\Router\Validators;

class Value extends Any
{
    /**
     * @see \Slab\Components\Router\Validators\Any::setChallenge()
     */
    public function setChallenge($newChallenge)
    {
        $this->pattern = $newChallenge;
    }

    /**
     * @see \Slab\Components\Router\Validators\Any::validate()
     */
    public function validate($segment)
    {
        return ($segment == $this->pattern);
    }
}