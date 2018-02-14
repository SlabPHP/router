<?php
/**
 * Numeric route pattern validator class
 *
 * @author Eric
 * @package Slab
 * @subpackage Router
 */
namespace Slab\Router\Validators;

class Numeric extends Any
{
    /**
     * Pattern
     *
     * @var string
     */
    protected $pattern = "#^[0-9]+$#";
}