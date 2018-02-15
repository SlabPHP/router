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
     * @return \Slab\Router\Configuration
     */
    private function getDefaultConfiguration()
    {
        $configuration = new \Slab\Router\Configuration();
        $logger = new Mocks\Log();

        $configuration
            ->setLog($logger)
            ->setConfigurationPaths([__DIR__.'/data/configuration/site1', __DIR__.'/data/configuration/site2'])
            ->addRouteFile('default.xml')
            ->addRouteFile('extra.xml')
            ->setDebugMode(true);

        return $configuration;
    }

    /**
     * Test router creation
     */
    public function testRouterCreation()
    {
        $configuration = $this->getDefaultConfiguration();

        $_SERVER['REQUEST_URI'] = '/test/url';
        $_SERVER['SERVER_NAME'] = 'localhost';

        $router = new \Slab\Router\Router($configuration);

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
     * @dataProvider testRoutingDataProvider
     */
    public function testRouting($url, $shouldResolve, $resolvesTo)
    {
        $configuration = $this->getDefaultConfiguration();

        $_SERVER['REQUEST_URI'] = $url;
        $_SERVER['SERVER_NAME'] = 'localhost';

        $router = new \Slab\Router\Router($configuration);

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
    public function testRoutingDataProvider()
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
        $configuration = $this->getDefaultConfiguration();

        $_SERVER['REQUEST_URI'] = '/blargh/value/thang/thing/77';
        $_SERVER['SERVER_NAME'] = 'localhost';

        $router = new \Slab\Router\Router($configuration);
        $router->determineSelectedRoute();
        $route = $router->getSelectedRoute();

        $params = $route->getParameters();

        $this->assertEquals('thang', $params->someVar);
        $this->assertEquals(77, $params->intVar);
        $this->assertEquals('1', $params->testValue);
        $this->assertEquals('string', $params->testString);
    }
}