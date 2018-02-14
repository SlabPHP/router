<?php
/**
 * Pattern Tests
 *
 * @package Slab
 * @subpackage Tests
 * @author Eric
 */
namespace Slab\Tests\Router;

class PatternTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test standard pattern
     */
    public function testDynamicPattern()
    {
        $pattern =  new \Slab\Router\Pattern('/test', '/{string:testString}/{numeric:testNumber}');

        $parameters = new \stdClass();
        $debugInfo = [];

        $isValid = $pattern->testPattern('/test/something/32', $parameters, $debugInfo);

        $this->assertEquals(true, $isValid);
        $this->assertEquals('something', $parameters->testString);
        $this->assertEquals(32, $parameters->testNumber);

        $isValid = $pattern->testPattern('/test/something/non-int', $parameters, $debugInfo);
        $this->assertEquals(false, $isValid);

        $isValid = $pattern->testPattern('/test/something', $parameters, $debugInfo);
        $this->assertEquals(false, $isValid);

        $isValid = $pattern->testPattern('/test', $parameters, $debugInfo);
        $this->assertEquals(false, $isValid);
    }

    /**
     * Tests having value pattern items
     */
    public function testValuePattern()
    {
        $pattern =  new \Slab\Router\Pattern('/test', '/thing/{string:testString}/thung/{numeric:testNumber}');

        $parameters = new \stdClass();
        $debugInfo = [];

        $isValid = $pattern->testPattern('/test/thing/something/thung/32', $parameters, $debugInfo);
        $this->assertEquals(true, $isValid);
        $this->assertEquals('something', $parameters->testString);
        $this->assertEquals(32, $parameters->testNumber);

        $isValid = $pattern->testPattern('/test/something/non-int', $parameters, $debugInfo);
        $this->assertEquals(false, $isValid);

        $isValid = $pattern->testPattern('/test/asdf/something', $parameters, $debugInfo);
        $this->assertEquals(false, $isValid);

        $isValid = $pattern->testPattern('/test/thing/something/blargh', $parameters, $debugInfo);
        $this->assertEquals(false, $isValid);
    }

    /**
     * Test regular pattern
     */
    public function testInvalidPattern()
    {
        $pattern = new \Slab\Router\Pattern('/test', '');

        $parameters = new \stdClass();
        $debugInfo = [];

        $isValid = $pattern->testPattern('/test/something/32', $parameters, $debugInfo);
        $this->assertEquals(false, $isValid);

        //This actually returns false because pattern is empty, pattern matchers are not for routes that don't have patterns set!
        $isValid = $pattern->testPattern('/test', $parameters, $debugInfo);
        $this->assertEquals(false, $isValid);
    }
}