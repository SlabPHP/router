<?php

namespace Slab\Tests\Router\Mocks;

class System implements \Slab\Components\SystemInterface
{
    public function config()
    {
        return null;
    }

    public function session()
    {
        return null;
    }

    public function log()
    {
        return null;
    }

    public function input()
    {
        return null;
    }

    public function router()
    {
        return null;
    }

    public function db()
    {
        return null;
    }

    public function cache()
    {
        return null;
    }

    public function routeRequest()
    {
        return null;
    }
}

