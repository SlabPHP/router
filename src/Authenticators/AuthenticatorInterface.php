<?php
/**
 * Router Authenticator Interface
 *
 * @package Slab
 * @subpackage Router
 * @author Eric
 */
namespace Slab\Router\Authenticators;

interface AuthenticatorInterface
{
    /**
     * Challenge Authentication, returns true/false based on if authentication is in place
     *
     * @return boolean
     */
    public function challengeAuthentication();
}