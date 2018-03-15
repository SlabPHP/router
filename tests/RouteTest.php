<?php
/**
 * Route Test
 *
 * @package Slab
 * @subpackage Tests
 * @author Eric
 */
namespace Slab\Tests\Router;

class RouteTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test simple route creation
     * @throws \Exception
     */
    public function testSimpleRoute()
    {
        $sampleRoute = new \stdClass();
        $sampleRoute->path = '/test';
        $sampleRoute->pattern = '';
        $sampleRoute->class = 'Slab\Tests\Router\Mocks\Controller';
        $sampleRoute->name = 'Test Route';

        $route = new \Slab\Router\Route($sampleRoute);

        $isValid = $route->isValid();

        $this->assertEquals(true, $isValid);
        $this->assertEquals('/test', $route->getPath(['something'=>'asdf']));

        $this->assertEquals('Slab\Tests\Router\Mocks\Controller', $route->getActualClass());
        $this->assertEquals('Test Route', $route->getName());
        $this->assertEquals(0, $route->getPriority());
        $this->assertEquals([], $route->getChildren());
        $this->assertEquals('', $route->getPatternString());
        $this->assertEquals(['test'], $route->getRoutingPath());
    }

    /**
     * Test invalid route generation
     * @throws \Exception
     */
    public function testInvalidRoute()
    {
        $sampleRoute = new \stdClass();

        $route = new \Slab\Router\Route($sampleRoute);

        $isValid = $route->isValid();
        $this->assertEquals(false, $isValid);

        $sampleRoute->path = '/test';
        $route = new \Slab\Router\Route($sampleRoute);
        $isValid = $route->isValid();
        $this->assertEquals(false, $isValid);

        $sampleRoute->name = 'Test Route';
        $route = new \Slab\Router\Route($sampleRoute);
        $isValid = $route->isValid();
        $this->assertEquals(false, $isValid);

        $sampleRoute->class = 'Slab\Tests\Router\Mocks\Controller';
        $route = new \Slab\Router\Route($sampleRoute);
        $isValid = $route->isValid();
        $this->assertEquals(true, $isValid);
    }

    /**
     * Test dynamic route
     * @throws \Exception
     */
    public function testDynamicRoute()
    {
        $sampleRoute = new \stdClass();
        $sampleRoute->path = '/test';
        $sampleRoute->pattern = '/{string:value1}/section/{numeric:value2}';
        $sampleRoute->class = 'Slab\Tests\Router\Mocks\Controller';
        $sampleRoute->name = 'Test Route';

        $route = new \Slab\Router\Route($sampleRoute);

        $isValid = $route->isValid();

        $this->assertEquals(true, $isValid);
        $this->assertEquals('/{string:value1}/section/{numeric:value2}', $route->getPatternString());

        $this->assertEquals('/test/one/section/two', $route->getPath(['value1'=>'one','value2'=>'two']));
    }
}