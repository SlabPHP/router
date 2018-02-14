<?php
/**
 * Configuration Object Test
 *
 * @package Slab
 * @subpackage Tests
 * @author Eric
 */
namespace Slab\Tests\Router;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Mostly useless tests
     */
    public function testConfigurationObject()
    {
        $configuration = new \Slab\Router\Configuration();

        $cacheObject = new Mocks\CacheDriver('test');
        $logObject = new Mocks\Log();

        $configuration
            ->setCache(true, 400, $cacheObject)
            ->setConfigurationPaths([__DIR__.'/data/configuration/site1', __DIR__.'/data/configuration/site2'])
            ->addRouteFile('default.xml')
            ->addRouteFile('extra.xml')
            ->setDebugMode(true)
            ->setLog($logObject)
            ->setGlobalAuthentication('\Slab\Tests\Router\Mocks\Authentication', ['parameter1'=>true]);

        $this->assertEquals($cacheObject, $configuration->getCacheDriver());
        $this->assertEquals(400, $configuration->getTableCacheTTL());
        $this->assertEquals($logObject, $configuration->getLog());
        $this->assertEquals([__DIR__.'/data/configuration/site1', __DIR__.'/data/configuration/site2'], $configuration->getConfigurationPaths());
        $this->assertEquals(['default.xml', 'extra.xml'], $configuration->getRouteFiles());
        $this->assertEquals(true, $configuration->getDebug());
        $this->assertEquals('\Slab\Tests\Router\Mocks\Authentication', $configuration->getGlobalAuthenticationClass());
        $this->assertEquals(['parameter1'=>true], $configuration->getGlobalAuthenticationParameters());
    }

}