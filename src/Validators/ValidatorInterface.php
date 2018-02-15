<?php
/**
 * Router Validator Interface
 *
 * @package Slab
 * @subpackage Router
 * @author Eric
 */
namespace Slab\Router\Validators;

interface ValidatorInterface
{
    /**
     * Validate this particular segment and returns the value if true
     *
     * @param string $segment
     * @return boolean
     */
    public function validate($segment);

    /**
     * Returns a value if the value changed during validation
     *
     * @return mixed
     */
    public function didValueChange();

    /**
     * Get output of validation
     *
     * @return array
     */
    public function getOutput();

}