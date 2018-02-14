<?php
/**
 * Validator Test
 *
 * @package Slab
 * @subpackage Tests
 * @author Eric
 */
namespace Slab\Tests\Router\Validators;

class AnyTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test validator
     */
    public function testValidator()
    {
        $validator = new \Slab\Router\Validators\Any();

        $this->assertEquals(true, $validator->validate('something'));
        $this->assertEquals(true, $validator->validate('12345'));
        $this->assertEquals(false, $validator->validate(''));
        $this->assertEquals(false, $validator->validate(false));
        $this->assertEquals(false, $validator->validate(null));
    }

    /**
     * Test the setChallenge function
     */
    public function testDynamicValidation()
    {
        $validator = new \Slab\Router\Validators\Any();
        $validator->setChallenge('testing');

        $this->assertEquals(true, $validator->validate('testing'));
        $this->assertEquals(false, $validator->validate('testing-asdfasdf'));
        $this->assertEquals(false, $validator->validate('testingasdfasdf'));
    }
}