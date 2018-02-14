<?php
/**
 * Base Router Authentication Class
 *
 * @package Slab
 * @subpackage Router
 * @author Eric
 */
namespace Slab\Router\Authenticators;

abstract class Base implements AuthenticatorInterface
{
    /**
     * Challenge Authentication, returns true/false based on if authentication is in place
     *
     * @return boolean
     */
    public function challengeAuthentication()
    {
        if ($this->testAuthentication()) {
            $this->onValidAuthentication();
        } else {
            $this->onInvalidAuthentication();
        }
    }

    /**
     * Test authentication, return true or false
     *
     * @return mixed
     */
    abstract protected function testAuthentication();

    /**
     * Actions to perform on valid authentication
     *
     * @return mixed
     */
    abstract protected function onValidAuthentication();

    /**
     * Actions to perform on invalid authentication
     *
     * @return mixed
     */
    abstract protected function onInvalidAuthentication();
}