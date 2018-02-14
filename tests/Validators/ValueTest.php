<?php
/**
 * Validator Test
 *
 * @package Slab
 * @subpackage Tests
 * @author Eric
 */
namespace Slab\Tests\Router\Validators;

class ValueTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test validator
     */
    public function testValidator()
    {
        $validator = new \Slab\Router\Validators\Value();

        $validator->setChallenge('something');
        $this->assertEquals(true, $validator->validate('something'));
        $this->assertEquals(false, $validator->validate('anything else'));
        $this->assertEquals(false, $validator->validate(''));
    }
}