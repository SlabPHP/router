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

class Router implements \Slab\Components\Router\RouterInterface
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
     * @var Route[]
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
     * @var int
     */
    private $cacheTTL = 3600;

    /**
     * @var bool
     */
    private $enableCache = false;

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
     * @var \Slab\Components\Cache\DriverInterface
     */
    private $cacheInterface = null;

    /**
     * Constructor builds out the internal arrays before processing the route table
     */
    public function __construct()
    {
        $this->getPathInfo();
    }

    /**
     * Route the request
     *
     * @param \Slab\Components\SystemInterface $system
     * @return \Slab\Components\Router\RouteInterface
     */
    public function routeRequest(\Slab\Components\SystemInterface $system)
    {
        $this->determineSelectedRoute();

        if (!empty($this->selectedRoute))
        {
            return $this->selectedRoute;
        }

        if (!empty($this->routeNameMap['404']))
        {
            return $this->routeNameMap['404'];
        }

        return null;
    }

    /**
     * Set cache
     *
     * @param $useCache
     * @param $tableTTL
     * @param \Slab\Components\Cache\DriverInterface $cacheObject
     * @return $this
     */
    public function setCache($useCache, $tableTTL, \Slab\Components\Cache\DriverInterface $cacheObject)
    {
        $this->cacheInterface = $cacheObject;
        $this->cacheTTL = $tableTTL;
        $this->enableCache = $useCache;

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
        $this->debugMode = $flag;

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
        if ($this->enableCache && !empty($this->cacheInterface))
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
        $this->routes = $this->cacheInterface->get('Routing_Table');

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
        if (empty($this->routeFiles) || !is_array($this->routeFiles)) {
            if (!empty($this->log))
            {
                $this->log->error("Missing configuration option routeFiles, or route file list in improper format.");
            }

            return false;
        }

        $routeTable = array();
        foreach ($this->routeFiles as $route) {
            foreach ($this->configurationPaths as $dir) {
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

            if (!empty($this->log))
            {
                $this->log->error($errorMessage);
            }
            return;
        }

        $routeClass = 'Slab\Router\Route';

        if (empty($routeClass)) {
            if (!empty($this->log))
            {
                $this->log->error("Failed to find suitable Route object.");
            }
            return;
        }

        foreach ($xml->xpath('route') as $routeObject) {
            /**
             * @var \Slab\Router\Route $route
             */
            try
            {
                $route = new $routeClass($routeObject);
            }
            catch (\Exception $exception)
            {
                if (!empty($this->log))
                {
                    $this->log->error("Failed to add route to table: " . $exception->getMessage());
                }
                continue;
            }


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
     * Determine the actually selected route from URL params
     */
    public function determineSelectedRoute()
    {
        if (empty($this->routes))
        {
            $this->getRoutingTable();
        }

        if (empty($this->segments)) {
            $this->segments = array('/');
        }

        $currentLevel = &$this->routes;
        //$parentSegment = '/';
        $index = 0;
        $isRoot = true;

        foreach ($this->segments as $segment) {
            $segmentIsInteger = is_numeric($segment);

            //Static route test, numeric segments are not allowed to be traversed
            if (!$segmentIsInteger && !empty($currentLevel[$segment])) {
                $this->addDebugMessage("Traversing " . $segment);

                $currentLevel = &$currentLevel[$segment];
                $index = 0;
                $isRoot = false;
                continue;
            }

            if ($isRoot && !empty($this->routes['/'])) {
                $currentLevel = &$this->routes['/'];
            }

            //We've hit a dead end here. The current tree branch does not have a static segment to match $segment
            if (!empty($currentLevel))
            {
                foreach ($currentLevel as $route) {
                    if ($route instanceof \Slab\Router\Route) {
                        $this->addDebugMessage("Checking route " . $route->getName());

                        $validationDebug = [];
                        if ($route->validateDynamicPattern($this->currentRequest, $validationDebug)) {
                            $this->addDebugMessage("Dynamic pattern match for route " . $route->getName());

                            $this->selectedRoute = &$route;
                            return true;
                        }
                    }

                    ++$index;
                }
            }

            $this->addDebugMessage("All hope is lost!");

            return false;
        }

        if (!empty($currentLevel))
        {
            foreach ($currentLevel as &$route)
            {
                if (!($route instanceof \Slab\Router\Route)) continue;

                /**
                 * @var \Slab\Router\Route $route
                 */
                if (!$route->isDynamic())
                {
                    $this->selectedRoute = &$route;
                    $this->addDebugMessage("Found matching static route: " . $this->selectedRoute->getName());

                    return true;
                }
                else
                {
                    $validationDebug = [];
                    if ($route->validateDynamicPattern($this->currentRequest, $validationDebug))
                    {
                        $this->selectedRoute = &$route;
                        $this->addDebugMessage("Found matching dynamic route: " . $this->selectedRoute->getName());

                        return true;
                    }
                }
            }

        }

        $this->addDebugMessage("Unable to find a matching route.");

        return false;
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
     * @return null|\Slab\Router\Route
     */
    public function getRouteByName($routeName)
    {
        if (!empty($this->routeNameMap[$routeName])) {
            return $this->routeNameMap[$routeName];
        }

        return null;
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
    private function addDebugMessage($message)
    {
        if ($this->debugMode) {
            $this->debug[] = $message;
        }
    }
}
