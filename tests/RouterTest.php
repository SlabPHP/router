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
            ->setDebugMode(true)
            ->setGlobalAuthentication('\Slab\Tests\Router\Mocks\Authentication', ['parameter1'=>true]);

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
        $this->assertEquals('Test URL #1', $route->getName());
    }
}