<?php
/**
 * Route Class
 *
 * @author salernoe
 * @package Slab
 * @subpackage Router
 */
namespace Slab\Router;

class Route
{
    /**
     * Route path
     *
     * @var string
     */
    private $path;

    /**
     * Route name
     *
     * @var string
     */
    private $name;

    /**
     * Class name for the route
     *
     * @var string
     */
    private $class;

    /**
     * Route priority
     *
     * @var integer
     */
    private $priority = 0;

    /**
     * If the route was created successfully.
     *
     * @var boolean
     */
    private $validRoute;

    /**
     * Pattern string for testing override routes
     *
     * @var string
     */
    private $patternString;

    /**
     * Pattern
     *
     * @var \Slab\Router\Pattern
     */
    private $pattern;

    /**
     * Parameters
     *
     * @var \stdClass
     */
    public $parameters;

    /**
     * Child Routes
     *
     * @var Route[]
     */
    private $children = [];

    /**
     * Validated data
     *
     * @var array
     */
    private $validatedData = [];

    /**
     * Create a new route
     *
     * @param mixed $initializingObject
     * @param \Psr\Log\LoggerInterface $logReference
     */
    public function __construct($initializingObject = null, $logReference = null)
    {
        if ($initializingObject instanceof \SimpleXMLElement) {
            $this->initializeFromSimpleXML($initializingObject);
        } else {
            $this->initializeFromHardcodedObject($initializingObject);
        }

        $this->validateRoute($logReference);
    }

    /**
     * Allow programmatic initialization of a route
     * @param \stdClass $object
     */
    private function initializeFromHardcodedObject($object)
    {
        //Typecast arrays to objects
        if (is_array($object)) $object = (object)$object;

        if (!empty($object->path)) {
            $this->path = $object->path;
        }

        if (!empty($object->name)) {
            $this->name = $object->name;
        }

        if (!empty($object->class)) {
            $this->class = $object->class;
        }

        if (!empty($object->priority)) {
            $this->priority = $object->priority;
        }

        if (!empty($object->pattern)) {
            if ($object->pattern instanceof Pattern)
            {
                $this->patternString = $object->pattern->getPatternString();
                $this->pattern = $object->pattern;
            }
            else
            {
                $this->patternString = $object->pattern;
                $this->pattern = new Pattern($this->path, $this->patternString);
            }
        }

        if (!empty($object->parameters)) {
            if (is_array($object->parameters)) {
                $this->parameters = (object)$object->parameters;
            } else {
                $this->parameters = $object->parameters;
            }
        }
    }


    /**
     * Initialize from a simple XML object
     *
     * @param \SimpleXMLElement $xmlObject
     */
    private function initializeFromSimpleXML($xmlObject)
    {
        $this->path = !empty($xmlObject->path) ? trim($xmlObject->path) : '/';
        $this->name = !empty($xmlObject->name) ? trim($xmlObject->name) : '';
        $this->class = !empty($xmlObject->class) ? trim($xmlObject->class) : '';

        $this->priority = !empty($xmlObject->priority) ? intval($xmlObject->priority) : 0;

        $this->patternString = !empty($xmlObject->pattern) ? (string)$xmlObject->pattern : null;
        $this->pattern = !empty($this->patternString) ? new Pattern($this->path, $this->patternString) : null;

        $this->parameters = new \stdClass();

        //Build route parameters into an object because SimpleXMLElements can't be memcached
        if (!empty($xmlObject->parameters) && $xmlObject->parameters->children()) {
            foreach ($xmlObject->parameters->children() as $parameter) {
                $parameterName = (string)$parameter->getName();
                $parameterValue = (string)$parameter;
                $parameterAttributes = [];

                $attributes = $parameter->attributes();
                foreach ($attributes as $attributeName => $attributeValue) {
                    $parameterAttributes[(string)$attributeName] = (string)$attributeValue;
                }

                $parameterObject = new \Slab\Router\Parameter($parameterName, $parameterValue, $parameterAttributes);

                if (!empty($this->parameters->$parameterName)) {
                    if (is_array($this->parameters->$parameterName)) {
                        $this->parameters->{$parameterName}[] = $parameterObject;
                    } else {
                        $this->parameters->$parameterName = array($this->parameters->$parameterName, $parameterObject);
                    }
                } else {
                    $this->parameters->$parameterName = $parameterObject;
                }
            }
        }
    }

    /**
     * Validates the route
     * @param \Psr\Log\LoggerInterface $logReference
     */
    private function validateRoute($logReference)
    {
        $valid = true;

        if (empty($this->name)) {
            if ($logReference) $logReference->error('Route does not specify a name. Path(' . $this->path . ') Class(' . $this->class . ')');
            $valid = false;
        }

        if (empty($this->path)) {
            if ($logReference) $logReference->error('Route does not specify a path. Name(' . $this->name . ') Class(' . $this->class . ')');
            $valid = false;
        } else {
            if ($this->path != '/' && $this->path[0] != '/') {
                if ($logReference) $logReference->error('Path ' . $this->path . ' must start with a forward slash. eg. /testing');
                $valid = false;
            }

            if ($this->path[mb_strlen($this->path) - 1] == '/') {
                $this->path = mb_substr($this->path, 0, mb_strlen($this->path) - 1);
            }
        }

        if (empty($this->class)) {
            if ($logReference) $logReference->error('Route does not specify a class. Name(' . $this->name . ') Path(' . $this->path . ')');
            $valid = false;
        }

        $this->validRoute = $valid;
    }

    /**
     * Returns valid state on the route
     */
    public function isValid()
    {
        return $this->validRoute;
    }

    /**
     * Add a child route to the tree
     *
     * @param Route $route
     */
    public function addChildRoute(Route $route)
    {
        $this->children[] = $route;
    }

    /**
     * Returns an array of the routing path
     *
     * @return array
     */
    public function getRoutingPath()
    {
        $tempPath = substr($this->path, 1);

        if (strpos($tempPath, '/') !== false) {
            $pathPieces = explode('/', $tempPath);
            return $pathPieces;
        } else if ($tempPath) {
            return array($tempPath);
        } else {
            return array();
        }
    }

    /**
     * Get the route class name
     *
     * @return string
     */
    public function getClass()
    {
        if (!$this->validRoute) {
            return false;
        }

        return $this->class;
    }

    /**
     * Validates a pattern against segments or a path
     *
     * @param string $path
     * @param array $debugInformation
     * @return true
     */
    public function validateDynamicPattern($path, &$debugInformation)
    {
        if (empty($this->pattern)) {
            return false;
        }

        $value = $this->pattern->testPattern($path, $this->parameters, $debugInformation);

        $this->validatedData = $this->pattern->getValidatedData();

        return $value;
    }

    /**
     * Get validated data
     *
     * @return array
     */
    public function getValidatedData()
    {
        return $this->validatedData;
    }

    /**
     * @return \stdClass
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Return the pattern string of a route
     */
    public function getPatternString()
    {
        return $this->patternString;
    }

    /**
     * Get route name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get actual class name
     *
     * @return string
     */
    public function getActualClass()
    {
        return $this->class;
    }

    /**
     * Get priorty
     *
     * @return number
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * Get path
     *
     * @return string
     */
    public function getPath($parameters = null)
    {
        if (empty($parameters) || !is_array($parameters) || empty($this->patternString)) {
            return $this->path;
        }

        //If an array of parameters is specified and a pattern string exists, we can build a path for the route
        $pattern = $this->patternString;
        foreach ($parameters as $parameterName => $parameterValue) {
            $pattern = preg_replace('#\[?\{[^:]+:' . preg_quote($parameterName, '#') . '\}\]?#', $parameterValue, $pattern);
        }

        //Delete remaining unmatched parameters
        $pattern = preg_replace('#\/\[?\{[^}]+\}\]?#', '', $pattern);

        return $this->path . $pattern;
    }

    /**
     * Get Children List
     *
     * @return mixed:\Slab\Router\Route
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @return bool
     */
    public function isDynamic()
    {
        return !empty($this->pattern);
    }

}