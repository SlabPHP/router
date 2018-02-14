<?php
/**
 * SlabPHP Router Configuration Object
 *
 * @package Slab
 * @subpackage Router
 * @author Eric
 */
namespace Slab\Router;

class Configuration
{
    /**
     * @var \Slab\Components\CacheDriverInterface
     */
    private $cacheInterface = null;

    /**
     * @var int
     */
    private $cacheTTL = 3600;

    /**
     * @var bool
     */
    private $enableCache = false;

    /**
     * @var bool
     */
    private $debug = false;

    /**
     * @var array
     */
    private $routeFiles = [];

    /**
     * @var array
     */
    private $configurationPaths = [];

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $log;

    /**
     * @var string
     */
    private $globalAuthenticationClass;

    /**
     * @var array
     */
    private $globalAuthenticationParameters = [];

    /**
     * Set cache
     *
     * @param $useCache
     * @param $tableTTL
     * @param \Slab\Components\CacheDriverInterface $cacheMechanism
     * @return $this
     */
    public function setCache($useCache, $tableTTL, \Slab\Components\CacheDriverInterface $cacheObject)
    {
        $this->cacheInterface = $cacheObject;
        $this->cacheTTL = $tableTTL;
        $this->enableCache = $useCache;

        return $this;
    }

    /**
     * @param $className
     * @param array $parameters
     * @return $this
     */
    public function setGlobalAuthentication($className, $parameters = [])
    {
        if ($className[0] != '\\')
        {
            $className = '\Slab\Router\Authenticators\\' . $className;
        }

        if (!class_exists($className))
        {
            //@todo Throw something here
            return $this;
        }

        $this->globalAuthenticationClass = $className;
        $this->globalAuthenticationParameters = $parameters;

        return $this;
    }

    /**
     * Set debug mode
     *
     * @param $flag
     * @return $this
     */
    public function setDebugMode($flag)
    {
        $this->debug = $flag;

        return $this;
    }

    /**
     * @param \Psr\Log\LoggerInterface $log
     * @return $this
     */
    public function setLog(\Psr\Log\LoggerInterface $log)
    {
        $this->log = $log;

        return $this;
    }

    /**
     * @return bool|\Slab\Components\CacheDriverInterface
     */
    public function getCacheDriver()
    {
        if (!empty($this->cacheInterface))
        {
            return $this->cacheInterface;
        }

        return false;
    }

    /**
     * Set configuration paths
     *
     * @param $paths
     * @return $this
     */
    public function setConfigurationPaths($paths)
    {
        $this->configurationPaths = $paths;

        return $this;
    }

    /**
     * Add route file
     *
     * @param $routeFile
     * @return $this
     */
    public function addRouteFile($routeFile)
    {
        $this->routeFiles[] = $routeFile;

        return $this;
    }

    /**
     * @return int
     */
    public function getTableCacheTTL()
    {
        return $this->cacheTTL;
    }

    /**
     * @return mixed
     */
    public function getDebug()
    {
        return $this->debug;
    }

    /**
     * @return array
     */
    public function getRouteFiles()
    {
        return $this->routeFiles;
    }

    /**
     * @return \Psr\Log\LoggerInterface
     */
    public function getLog()
    {
        return $this->log;
    }

    /**
     * @return mixed
     */
    public function getConfigurationPaths()
    {
        return $this->configurationPaths;
    }

    /**
     * @return mixed
     */
    public function getGlobalAuthenticationClass()
    {
        return $this->globalAuthenticationClass;
    }

    /**
     * @return mixed
     */
    public function getGlobalAuthenticationParameters()
    {
        return $this->globalAuthenticationParameters;
    }
}