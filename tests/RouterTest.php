<?php
/**
 * Router Tests
 *
 * @package Slab
 * @subpackage Tests
 * @author Eric
 */
namespace Slab\Tests\Router;

class RouterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test router creation
     */
    public function testRouterCreation()
    {
        $_SERVER['REQUEST_URI'] = '/test/url';
        $_SERVER['SERVER_NAME'] = 'localhost';

        $router = new \Slab\Router\Router();
        $logger = new \Slab\Tests\Components\Mocks\Log();
        $router
            ->setLog($logger)
            ->setConfigurationPaths([__DIR__.'/data/configuration/site1', __DIR__.'/data/configuration/site2'])
            ->addRouteFile('default.xml')
            ->addRouteFile('extra.xml')
            ->setDebugMode(true);

        $this->assertEquals('http://localhost', $router->baseHREF);
        $this->assertEquals('http://localhost/test/url', $router->currentHREF);
        $this->assertEquals('/test/url', $router->currentRequest);

        $router->determineSelectedRoute();

        $route = $router->getSelectedRoute();
        $this->assertNotEmpty($route);
    }

    /**
     * @param $url
     * @param $shouldResolve
     * @param $resolvesTo
     * @dataProvider dataProviderRouting
     */
    public function testRouting($url, $shouldResolve, $resolvesTo)
    {
        $_SERVER['REQUEST_URI'] = $url;
        $_SERVER['SERVER_NAME'] = 'localhost';

        $router = new \Slab\Router\Router();
        $logger = new \Slab\Tests\Components\Mocks\Log();
        $router
            ->setLog($logger)
            ->setConfigurationPaths([__DIR__.'/data/configuration/site1', __DIR__.'/data/configuration/site2'])
            ->addRouteFile('default.xml')
            ->addRouteFile('extra.xml')
            ->setDebugMode(true);

        $router->determineSelectedRoute();

        $route = $router->getSelectedRoute();
        if ($shouldResolve)
        {
            $this->assertNotEmpty($route);
            $this->assertEquals($resolvesTo, $route->getName());
        }
        else
        {
            $this->assertEmpty($route);
        }
    }

    /**
     * Route Testing Data Provider
     *
     * @return array
     */
    public function dataProviderRouting()
    {
        return [
            ['/test/url', true, 'Test URL #1'],
            ['/', false, ''],
            ['/test', false, ''],
            ['/blargh', false, ''],
            ['/blargh/test', true, 'Test URL #2'],
            ['/blargh/value/something/thing/45', true, 'Test URL #3'],
            ['/blargh/value/thing/thing/asdf', false, ''],
            ['/blargh/value/thing/thing', false, ''],
            ['/blargh/value/thing', false, ''],
            ['/blargh/value/thing', false, ''],
            ['/duplicate-route', true, 'Test URL #4'],
            ['/duplicate-route-by-name', true, 'Test URL #4'],
            ['/optional-route', true, 'Test URL #5'],
            ['/optional-route/something', true, 'Test URL #5'],
            ['/override-route', true, 'Test URL #6b'],
            ['/static', true, 'Test URL #7'],
            ['/static/asdfasdf', false, ''],
        ];
    }

    /**
     * Test route parameters
     */
    public function testRouteParameters()
    {
        $_SERVER['REQUEST_URI'] = '/blargh/value/thang/thing/77';
        $_SERVER['SERVER_NAME'] = 'localhost';

        $router = new \Slab\Router\Router();
        $logger = new \Slab\Tests\Components\Mocks\Log();
        $router
            ->setLog($logger)
            ->setConfigurationPaths([__DIR__.'/data/configuration/site1', __DIR__.'/data/configuration/site2'])
            ->addRouteFile('default.xml')
            ->addRouteFile('extra.xml')
            ->setDebugMode(true);

        $router->determineSelectedRoute();
        $route = $router->getSelectedRoute();

        $this->assertNotEmpty($route);

        $params = $route->getParameters();

        $this->assertEquals('thang', $params->someVar);
        $this->assertEquals(77, $params->intVar);
        $this->assertEquals('1', $params->testValue);
        $this->assertEquals('string', $params->testString);
    }

    /**
     * Test validated data
     */
    public function testRouteValidatedData()
    {
        $_SERVER['REQUEST_URI'] = '/validator/throng';
        $_SERVER['SERVER_NAME'] = 'localhost';

        $router = new \Slab\Router\Router();
        $logger = new \Slab\Tests\Components\Mocks\Log();
        $router
            ->setLog($logger)
            ->setConfigurationPaths([__DIR__.'/data/configuration/site1', __DIR__.'/data/configuration/site2'])
            ->addRouteFile('default.xml')
            ->addRouteFile('extra.xml')
            ->setDebugMode(true);

        $router->determineSelectedRoute();
        $route = $router->getSelectedRoute();

        $this->assertNotEmpty($route);

        $params = $route->getParameters();

        $this->assertNotEmpty($params);
        $this->assertNotEmpty($params->testValue);
        $this->assertEquals('throng', $params->testValue);

        $validated = $route->getValidatedData();

        $this->assertNotEmpty($validated);
        $this->assertNotEmpty($validated->reversed);

        $this->assertEquals('gnorht', $validated->reversed);
    }

    /**
     * Another test
     */
    public function testRouteAController()
    {
        $_SERVER['REQUEST_URI'] = '/test/url';
        $_SERVER['SERVER_NAME'] = 'localhost';

        $router = new \Slab\Router\Router();
        $logger = new \Slab\Tests\Components\Mocks\Log();
        $router
            ->setLog($logger)
            ->setConfigurationPaths([__DIR__.'/data/configuration/site1', __DIR__.'/data/configuration/site2'])
            ->addRouteFile('default.xml')
            ->addRouteFile('extra.xml')
            ->setDebugMode(true);

        $systemMock = new\Slab\Tests\Components\Mocks\System();

        $result = $router->routeRequest($systemMock);
        $this->assertNotEmpty($result);
        $this->assertSame('\Slab\Tests\Components\Mocks\Router\Controller', $result->getClass());
    }
}