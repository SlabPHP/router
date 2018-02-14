<?php
/**
 * Router Class
 *
 * The router takes the current URI data and parses a routing table. This gives us
 * the requested controller. The router can then begin the routing of the user's
 * request properly.
 *
 * @author Eric
 * @package Slab
 * @subpackage Router
 */
namespace Slab\Router;

class Router
{
    /**
     * URI Segments
     *
     * @var string[]
     */
    private $segments = array();

    /**
     * Routing table
     *
     * @var Route
     */
    private $routes;

    /**
     * Actual selected route
     *
     * @var Route
     */
    private $selectedRoute;

    /**
     * Base HREF
     * eg. http://www.example.com/ when url is http://www.example.com/whatever/stuff.html?test=true
     *
     * @var string
     */
    public $baseHREF;

    /**
     * Current specified HREF,
     * eg. http://www.example.com/whatever/stuff.html when url is http://www.example.com/whatever/stuff.html?test=true
     *
     * @var string
     */
    public $currentHREF;

    /**
     * Just the request portion of the URI for routing
     * eg. /whatever/stuff.html when url is http://www.example.com/whatever/stuff.html?test=true
     *
     * @var string
     */
    public $currentRequest;

    /**
     * Full request including query params
     * eg. /whatever/stuff.html?test=true when url is http://www.example.com/whatever/stuff.html?test=true
     *
     * @var string
     */
    public $fullRequest;

    /**
     * Debug information about routing
     *
     * @var string
     */
    private $debug = array();

    /**
     * Debug mode
     *
     * @var boolean
     */
    private $debugMode = true;

    /**
     * Route Name Mapping
     *
     * @var string[Route]
     */
    private $routeNameMap = [];

    /**
     * Is it a request to a / homepage url? Used for determining if we should
     * substitute the homepage with a welcome to the framework message.
     *
     * @var bool
     */
    private $isHomepage = false;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * Constructor builds out the internal arrays before processing the route table

     * @param Configuration $configuration
     */
    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;

        $this->getPathInfo();

        $this->getRoutingTable();
    }

    /**
     * Make sure the request URI fits how we want it to
     *
     * @param string $requestURI
     */
    private function validateRequestURI(&$requestURI)
    {
        if ($requestURI != '/' && substr($requestURI, -1) == '/') {
            $url = rtrim($requestURI, '/');

            if (php_sapi_name() === 'cli') {
                exit("Please ensure the URL you typed in does not have a trailing backslash.\n");
            }

            header("Location: " . $url);
            exit();
        }
    }

    /**
     * Gets the path info and parses out the segment structure
     */
    private function getPathInfo()
    {
        $requestURI = !empty($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';

        $this->validateRequestURI($requestURI);

        if (empty($requestURI)) {
            return;
        }

        $request = $requestURI;

        $this->fullRequest = $this->currentRequest = $request;
        $this->addDebugMessage("Full request " . $this->fullRequest);

        $secure = !empty($_SERVER['HTTPS']);
        $this->addDebugMessage($secure ? "Secure routing." : "Non-secure routing.");

        $this->baseHREF = "http" . ($secure ? 's' : '') . '://' . $_SERVER['SERVER_NAME'];

        $this->currentHREF = $this->baseHREF . $request;

        $queryStringIndex = strpos($request, '?');

        $startsWithSlash = ($request[0] == '/' ? 1 : 0);

        if ($queryStringIndex !== false) {
            $this->currentRequest = substr($request, 0, $queryStringIndex);

            $request = substr($request, $startsWithSlash, $queryStringIndex - 1);
        } else {
            $request = substr($request, $startsWithSlash);
        }

        if (!empty($request)) {
            $this->segments = explode('/', $request);
        }

        $this->isHomepage = (empty($request) || $request == '/');

        $this->checkForTrailingSlashRedirect();
    }

    /**
     * Checks for a trailing slash or something in the url
     */
    private function checkForTrailingSlashRedirect()
    {
        if (!empty($this->segments) && empty($this->segments[count($this->segments) - 1])) {
            //Trim off the empty segment
            array_pop($this->segments);

            $newUrl = $this->baseHREF . '/' . implode('/', $this->segments);

            header("Location: " . $newUrl);
            exit();
        }
    }

    /**
     * Get the routing table from cache or otherwise
     */
    public function getRoutingTable()
    {
        if ($this->configuration->getCacheDriver())
        {
            return $this->fetchRoutingTableFromCache();
        }

        return $this->fetchRoutingTable();
    }

    /**
     * Fetch the routing table without caching
     *
     * @return \Slab\Router\Route
     */
    private function fetchRoutingTable()
    {
        $this->routes = $this->loadRoutingTable();

        if (!empty($this->routes)) {
            $this->traverseRouteListAndBuildMap();
        }

        return $this->routes;
    }

    /**
     * Fetch the routing table using the default cache provider
     *
     * @return \Slab\Router\Route
     */
    private function fetchRoutingTableFromCache()
    {
        $this->routes = $this->configuration->getCacheDriver()->get('Routing_Table');

        if (empty($this->routes))
        {
            $this->routes = $this->loadRoutingTable();
        }

        if (!empty($this->routes)) {
            $this->traverseRouteListAndBuildMap();
        }

        return $this->routes;
    }

    /**
     * Traverses the list of routes and builds a name -> route map reference list
     *
     * @param Route $currentNode
     */
    private function traverseRouteListAndBuildMap($currentNode = null)
    {
        if (empty($currentNode)) {
            $this->traverseRouteListAndBuildMap($this->routes);
            return;
        }

        if (is_array($currentNode)) {
            foreach ($currentNode as $node) {
                $this->traverseRouteListAndBuildMap($node);
            }

            return;
        }

        if (!($currentNode instanceof Route)) {
            return;
        }

        $currentNodeName = $currentNode->getName();

        if (!empty($this->routeNameMap[$currentNodeName])) {
            $this->addDebugMessage("Duplicate route name " . $currentNodeName . " detected.");
        }

        $this->routeNameMap[$currentNodeName] =& $currentNode;

        if ($currentNode->getChildren()) {
            foreach ($currentNode->getChildren() as $child) {
                $this->traverseRouteListAndBuildMap($child);
            }
        }
    }

    /**
     * Load routing table from files
     *
     */
    public function loadRoutingTable()
    {
        $routeFiles = $this->configuration->getRouteFiles();

        if (empty($routeFiles) || !is_array($routeFiles)) {
            if ($this->configuration->getLog())
            {
                $this->configuration->getLog()->error("Missing configuration option routeFiles, or route file list in improper format.");
            }

            return false;
        }

        $configDirs = $this->configuration->getConfigurationPaths();

        $routeTable = array();
        foreach ($routeFiles as $route) {
            foreach ($configDirs as $dir) {
                $fileName = $dir . '/' . $route;
                if (file_exists($fileName)) {
                    $this->loadRouteFile($routeTable, $fileName);
                    $this->addDebugMessage("Loaded route file: " . $fileName);
                } else {
                    $this->addDebugMessage("Failed to load route file: " . $fileName);
                }
            }
        }

        return $routeTable;
    }

    /**
     * Load an XML route file from a fully qualified path name
     *
     * @param array $routeTable
     * @param string $fileName
     */
    private function loadRouteFile(&$routeTable, $fileName)
    {
        libxml_use_internal_errors(true);

        $xml = simplexml_load_file($fileName);

        if (empty($xml)) {
            $errorMessage = "Failed to parse XML route file: " . $fileName . "";

            foreach (libxml_get_errors() as $error) {
                $errorMessage .= "\n" . $error->message;
            }

            if ($this->configuration->getLog())
            {
                $this->configuration->getLog()->error($errorMessage);
            }
            return;
        }

        //@todo findClass
        $routeClass = 'Slab\Router\Route';

        if (empty($routeClass)) {
            if ($this->configuration->getLog())
            {
                $this->configuration->getLog()->error("Failed to find suitable Route object.");
            }
            return;
        }

        foreach ($xml->xpath('route') as $routeObject) {
            /**
             * @var \Slab\Router\Route $route
             */
            $route = new $routeClass($routeObject);

            if (!$route->isValid()) {
                $this->addDebugMessage('Skipping invalid route!');
                continue;
            }

            $path = $route->getRoutingPath();

            //Here we'll iterate through the current routing tree on $currentLevel checking static routes
            if ($path) {
                $currentLevel =& $routeTable;
                //$pathDepth = count($path);

                //Loop through each path level and add that level of hierarchy to our routing table
                foreach ($path as $pathFolder) {
                    if (empty($currentLevel[$pathFolder])) {
                        $currentLevel[$pathFolder] = array();
                    }

                    $currentLevel =& $currentLevel[$pathFolder];
                }

                if (!empty($currentLevel)) {
                    //Check each item in this list's pattern string against the one we currently have.
                    //If a pattern string matches (empty or not) that means we have an overridden path

                    $overridden = false;
                    foreach ($currentLevel as $index => $subRoute) {
                        if (($subRoute instanceof \Slab\Router\Route) &&
                            ($subRoute->getPatternString() == $route->getPatternString())
                        ) {
                            $currentLevel[$index] = $route;
                            $overridden = true;
                        }
                    }

                    if (!$overridden) {
                        $currentLevel[] = $route;
                    }
                } else {
                    $currentLevel[] = $route;
                }
            } else {
                $routeTable['/'][] = $route;
            }
        }
    }

    /**
     * Handle global override route
     * @todo reimplement this properly
     */
    private function handleGlobalOverrideRoute()
    {
        return;
    }

    /**
     * Handle global authentication if required
     */
    private function handleGlobalAuthentication()
    {
        // Don't do global auth on CLI
        if (php_sapi_name() == "cli") return;

        // Don't do global auth if global auth hasn't been setup, duh
        if (!$this->configuration->getGlobalAuthenticationClass()) return;

        // Allow a selected route to disable global auth with a disableAuthentication parameter
        if (!empty($this->selectedRoute) && !empty($this->selectedRoute->parameters->disableAuthentication)) {
            return;
        }

        /**
         * @var \Slab\Router\Authenticators\Base $authenticator
         */
        $authenticatorClass = $this->configuration->getGlobalAuthenticationClass();

        if (empty($authenticatorClass)) {
            if ($this->configuration->getLog())
            {
                $this->configuration->getLog()->error("Invalid authenticator class specified: " . $this->configuration->getGlobalAuthenticationClass());
            }
            return;
        }

        $authenticator = new $authenticatorClass();

        $authenticator->challengeAuthentication();
    }

    /**
     * Determine the actually selected route from URL params
     */
    public function determineSelectedRoute()
    {
        if (empty($this->segments)) {
            $this->segments = array('/');
        }

        $currentLevel = $this->routes;
        //$parentSegment = '/';
        $index = 0;
        $isRoot = true;

        foreach ($this->segments as $segment) {
            $segmentIsInteger = is_numeric($segment);

            //Static route test, numeric segments are not allowed to be traversed
            if (!$segmentIsInteger && !empty($currentLevel[$segment])) {
                $this->addDebugMessage("Traversing " . $segment);

                $currentLevel = $currentLevel[$segment];
                //$parentSegment = $segment;
                $index = 0;
                $isRoot = false;
                continue;
            }

            $lastStand = $currentLevel;

            if ($isRoot && !empty($this->routes['/'])) {
                $lastStand = $this->routes['/'];
            }

            //We've hit a dead end here. The current tree branch does not have a static segment to match $segment
            if (!empty($lastStand)) {
                foreach ($lastStand as $route) {
                    if ($route instanceof \Slab\Router\Route) {
                        $this->addDebugMessage("Checking route " . $route->getName());

                        if ($route->validateDynamicPattern($this->currentRequest, $this->debug)) {
                            $this->addDebugMessage("Dynamic pattern match for route " . $route->getName());

                            $this->selectedRoute = $route;
                            return true;
                        }
                    }

                    ++$index;
                }
            }

            $this->addDebugMessage("All hope is lost!");

            return false;
        }

        if (!empty($currentLevel[$index])) {
            $this->selectedRoute = $currentLevel[$index];
            $this->addDebugMessage("Found matching static route: " . $this->selectedRoute->getName());

            return true;
        }

        $this->addDebugMessage("Unable to find a matching static route.");

        return false;
    }

    /**
     * Begin the routing of the selected route
     *
     * @return boolean
     */
    public function routeRequest()
    {
        $this->handleGlobalOverrideRoute();

        $this->handleGlobalAuthentication();

        if (empty($this->selectedRoute)) {
            if ($this->isHomepage) {
                $this->handleWelcomeToFramework();
                return false;
            }

            $this->handle404();
            return false;
        }

        if (!$this->routeIndividualPage($this->selectedRoute)) {
            $this->handle404();
            return false;
        }

        return true;
    }

    /**
     * Welcome to the framework!
     * @todo Move this out of the router
     */
    public function handleWelcomeToFramework()
    {
        $className = '\Slab\Controllers\Template';

        $route = new Route(array(
            'path' => $this->currentRequest,
            'class' => $className,
            'name' => 'Welcome to SlabPHP',
            'parameters' => array(
                'pageTitle' => 'Welcome to SlabPHP!',
                'subTemplateName' => 'pages/debug/welcome.php',
                'pageDescription' => 'Your SlabPHP application has been successfully setup!'
            )
        ));

        $this->routeIndividualPage($route);

        exit();
    }


    /**
     * Handle 404 pages
     * @todo move this out of the router
     */
    public function handle404()
    {
        //Proxy Attempt Check
        $proxyCheck = substr($this->currentRequest, 0, 7);
        if ($proxyCheck == 'http://' || $proxyCheck == 'https:/') {
            header($_SERVER["SERVER_PROTOCOL"] . " 403 Forbidden");

            if ($this->configuration->getLog())
            {
                $this->configuration->getLog()->notice("Proxy attempt for " . $this->currentRequest . " denied.");
            }

            echo 'There is no proxy here.';
            exit();
        }

        $className = '\Slab\Controllers\Error'; //$this->findClass('Controllers\Error', false);

        $route = new Route(array(
            'path' => $this->currentRequest,
            'class' => $className,
            'name' => 'Dynamic Error',
            'parameters' => array(
                'errorCode' => 404
            )
        ));

        $this->routeIndividualPage($route);

        exit();
    }

    /**
     * Route an individual page
     *
     * @param Route $route
     * @return boolean
     */
    public function routeIndividualPage(Route $route)
    {
        $this->selectedRoute = $route;

        $className = $route->getClass();

        if (empty($className)) return false;

        if (!class_exists($className))
        {
            if ($this->configuration->getLog())
            {
                $this->configuration->getLog()->error("Invalid controller class specified: " . $className);
            }

            return false;
        }

        $controller = new $className();

        if (!($controller instanceof \Slab\Components\Router\RoutableControllerInterface))
        {
            if ($this->configuration->getLog())
            {
                $this->configuration->getLog()->error("Controller does not adopt RoutableControllerInterface: " . $className);
                return false;
            }
        }

        //Copy over validated data
        if ($route->getValidatedData())
        {
            foreach ($route->getValidatedData() as $parameterName => $value)
            {
                $route->parameters->$parameterName = $value;
            }
        }

        $controller->executeControllerLifecycle();

        return true;
    }

    /**
     * Return the currently selected route
     *
     * @return Route
     */
    public function getSelectedRoute()
    {
        return $this->selectedRoute;
    }

    /**
     * Gets a route by name
     *
     * @param string $routeName
     * @return \Slab\Router\Route
     */
    public function getRouteByName($routeName)
    {
        if (!empty($this->routeNameMap[$routeName])) {
            return $this->routeNameMap[$routeName];
        }

        return false;
    }

    /**
     * Get debug information
     *
     * @return string
     */
    public function getDebugInformation()
    {
        return $this->debug;
    }

    /**
     * Adds a debug message
     *
     * @param string $message
     */
    protected function addDebugMessage($message)
    {
        if ($this->debugMode) {
            $this->debug[] = $message;
        }
    }
}
