<?php
/**
 * Authenticator Mock
 *
 * @package Slab
 * @subpackage Tests
 * @author Eric
 */
namespace Slab\Tests\Router\Mocks;

class Authentication implements \Slab\Router\Authenticators\AuthenticatorInterface
{
    /**
     * Challenge Authentication, returns true/false based on if authentication is in place
     *
     * @return boolean
     */
    public function challengeAuthentication()
    {
        return true;
    }
}