<?php
/**
 * Validator Test
 *
 * @package Slab
 * @subpackage Tests
 * @author Eric
 */
namespace Slab\Tests\Router\Validators;

class NumericTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test validator
     */
    public function testValidator()
    {
        $validator = new \Slab\Router\Validators\Numeric();

        $this->assertEquals(true, $validator->validate('15673'));
        $this->assertEquals(true, $validator->validate('0'));
        $this->assertEquals(false, $validator->validate('asdf'));
        $this->assertEquals(false, $validator->validate(''));
    }
}