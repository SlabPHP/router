<?php
/**
 * Validator Test
 *
 * @package Slab
 * @subpackage Tests
 * @author Eric
 */
namespace Slab\Tests\Router\Validators;

class TextTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test validator
     */
    public function testValidator()
    {
        $validator = new \Slab\Router\Validators\Text();

        $this->assertEquals(true, $validator->validate('some text'));
        $this->assertEquals(true, $validator->validate('21q34'));
        $this->assertEquals(true, $validator->validate('thing'));
        $this->assertEquals(true, $validator->validate('some_thing+something-else'));
        $this->assertEquals(false, $validator->validate('argh!'));
        $this->assertEquals(false, $validator->validate('#$%^&*()'));
        $this->assertEquals(false, $validator->validate(''));
    }
}