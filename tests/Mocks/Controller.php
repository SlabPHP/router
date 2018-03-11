<?php
/**
 * Mock Controller for Route
 *
 * @package Slab
 * @subpackage Tests
 * @author Eric
 */
namespace Slab\Tests\Router\Mocks;

class Controller implements \Slab\Components\Router\RoutableControllerInterface
{
    /**
     * @param \Slab\Components\SystemInterface $system
     * @return mixed
     */
    public function setSystemReference(\Slab\Components\SystemInterface $system)
    {
        echo 'System set!';

        return $this;
    }

    /**
     * Execute the controller lifecycle
     *
     * @return mixed
     */
    public function executeControllerLifecycle()
    {
        echo 'Executed!';

        return true;
    }
}