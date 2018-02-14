<?php
/**
 * Regex validator class, this will always match true for a segment
 *
 * @author Eric
 * @package Slab
 * @subpackage Router
 */
namespace Slab\Router\Validators;

class Any extends Base
{
    /**
     * Pattern
     *
     * @var string
     */
    protected $pattern = "#^.*$#";

    /**
     * Set the challenge, should be a regular expression without the delimiters
     * eg. [a-zA-Z0-9]+
     *
     * @param string $newChallenge
     */
    public function setChallenge($newChallenge)
    {
        $this->pattern = '#^' . preg_quote($newChallenge, '#') . '$#';
    }

    /**
     * Validate this particular segment and returns the value if true
     *
     * @param string $segment
     * @return boolean
     */
    public function validate($segment)
    {
        if ($segment == '') return false;

        return preg_match($this->pattern, $segment);
    }
}