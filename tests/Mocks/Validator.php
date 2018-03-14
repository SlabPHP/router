<?php
/**
 * Validator that returns true and returns a validated value
 *
 * @package Slab
 * @subpackage Tests
 * @author Eric
 */
namespace Slab\Tests\Router\Mocks;

class Validator extends \Slab\Router\Validators\Base
{
    /**
     * Validate this particular segment and returns the value if true
     *
     * @param string $segment
     * @return boolean
     */
    public function validate($segment)
    {
        $this->passOutput('reversed', strrev($segment));

        return true;
    }
}