<?php
/**
 * Validator Test
 *
 * @package Slab
 * @subpackage Tests
 * @author Eric
 */
namespace Slab\Tests\Router\Validators;

class DateTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test validators
     */
    public function testValidator()
    {
        $validator = new \Slab\Router\Validators\Date();

        $dateObject = new \DateTime('2017-04-13');

        $isValid = $validator->validate($dateObject->format('Y-m-d'));

        $this->assertEquals(true, $isValid);
        $this->assertEquals($dateObject, $validator->didValueChange());
    }

    /**
     * Test failure
     */
    public function testFailure()
    {
        $validator = new \Slab\Router\Validators\Date();

        $isValid = $validator->validate('sAUSAGE!');

        $this->assertEquals(false, $isValid);
    }
}