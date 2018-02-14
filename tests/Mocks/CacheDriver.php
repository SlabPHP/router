<?php
/**
 * CacheDriverInterface mock
 *
 * @package Slab
 * @subpckage Tests
 *
 */
namespace Slab\Tests\Router\Mocks;

class CacheDriver implements \Slab\Components\CacheDriverInterface
{
    /**
     * Constructor stores a provider instance
     *
     * @param string $provider
     */
    public function __construct($provider)
    {

    }

    /**
     * Retrieve data from memcache
     *
     * @param string $key
     */
    public function get($key)
    {
        return 'get';
    }

    /**
     * Saves data in cache
     *
     * @param string $key
     * @param mixed $data
     * @param integer $ttl
     * @return boolean
     */
    public function set($key, $data, $ttl = 3600)
    {
        return 'set';
    }

    /**
     * Delete a key from memcache
     *
     * @param string $key
     * @return boolean
     */
    public function delete($key)
    {
        return 'deleted';
    }

    /**
     * Get a provider's interface, if available
     *
     * @return mixed
     */
    public function getInterface()
    {
        return 'interface';
    }

    /**
     * Do a cache request
     *
     * @param mixed $request
     * @return mixed
     */
    public function execute($request)
    {
        return 'executed';
    }
}

