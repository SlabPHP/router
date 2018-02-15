# SlabPHP Router

This router library will take specially crafted XML files, build a routing table, and determine if a route can be matched to the current REQUEST_URI parameters.

This router probably pales in comparison to many other modern open source routers. We're well aware XML is in many cases frowned upon. This library was written many years ago. Please see the main SlabPHP documentation for more information about this and all SlabPHP libraries.

## Router Setup and Usage

First import the library

    composer require slabphp/router

Then configure and instantiate your router.

    $configuration = new \Slab\Router\Configuration();

    $configuration
        ->setConfigurationPaths('/framework/configs','/site/configs'])
        ->addRouteFile('default.xml');

    $router = new \Slab\Router($configuration);

Next write your route files and resolve some routes!

    $router->determineSelectedRoute();
    $route = $router->getSelectedRoute();

Based on the values of $_SERVER['REQUEST_URI'] and the routes in /framework/configs/default.xml and /site/configs/default.xml, you will either get false (a 404 condition) or a \Slab\Router\Route object.

## Route Creation

### Static Routes

Depending on how you configure the router, a route file may be anywhere. But here is an example of a route file:

    <?xml version="1.0" encoding="UTF-8" ?>
    <routes>
        <route>
            <path>/</path>
            <name>Homepage</name>
            <class>\Namespace\Path\To\Your\Controller</class>
            <parameters>
                <someRouteParameter><![CDATA[This could be anything really. Maybe a page title?]]></testValue>
            </parameters>
        </route>
    </routes>

### Dynamic Routes (Pattern Validation)

You can also have dynamic routes that use pattern validators. For example:

    <route>
        <path>/something</path>
        <name>A Dynamic URL Path</name>
        <class>\Some\Controller</class>
        <pattern>/value/{string:someVar}/thing/{numeric:intVar}</pattern>
        <parameters>
            <testValue>1</testValue>
            <testString>string</testString>
        </parameters>
    </route>

This would match a URL that comes in looking like _/something/value/my-first-string/thing/32_ Notice the _{string:someVar}_ and _{numeric:intVar}_. These are mechanisms that tell the SlabPHP router to use a specific validator class and the variable name to store it in. You can create your own custom validators and simply specify their entire classname. For example, a blog post may have something like this:

    <route>
        <name>Blog URL</name>
        <class>\Some\Controller</class>
        <path>/</path>
        <pattern>/{numeric:year}}/{numeric:month}/{\My\Blog\Router\Validators\PostSlug:postSlug}</pattern>
        <parameters>
            <testValue>1</testValue>
            <testString>string</testString>
        </parameters>
    </route>

As long as \My\Blog\Router\Validators\PostSlug implements the correct interface, and returns true/false, you can fail this route if the post slug is wrong.

There are some built-in validators but many are application specific.

* __string__ - will match a string with a set of characters; a-z, 0-9, underscore, plus +, and a space.
* __date__ - will match any date that will work in the constructor to new \DateTime()
* __numeric__ - will match a number
* __value__ - matches an exact value, this is used internally for specific url segment values in pattern fields
* __any__ - matches anything