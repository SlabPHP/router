<?php
/**
 * Parameter Test Class
 *
 * @package Slab
 * @subpackage Tests
 * @author Eric
 */
namespace Slab\Tests\Router;

class ParameterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Meh, not much to test here
     */
    public function testParameters()
    {
        $parameter = new \Slab\Router\Parameter('testName', 'testValue', ['attribute1'=>true, 'attribute2'=>false]);

        $this->assertEquals('testValue', (string)$parameter);
        $this->assertEquals(['attribute1'=>true,'attribute2'=>false], $parameter->getAttributes());
    }
}