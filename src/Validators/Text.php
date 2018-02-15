<?php
/**
 * String route pattern validator class
 *
 * @author Eric
 * @package Slab
 * @subpackage Router
 */
namespace Slab\Router\Validators;

class Text extends Any
{
    /**
     * Pattern
     *
     * @var string
     */
    protected $pattern = "#^[a-zA-Z0-9_\\s+.-]+$#";

    /**
     * Validate this particular segment and returns the value if true
     *
     * @param string $segment
     * @return boolean
     */
    public function validate($segment)
    {
        if ($segment == '') return false;

        $segment = str_replace(' ', '_', urldecode($segment));
        $result = preg_match($this->pattern, $segment);

        return $result;
    }
}