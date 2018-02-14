<?php
/**
 * Route pattern, encapsulates a pattern string and allows validations against it
 *
 * @author Eric
 * @package Slab
 * @subpackage Router
 */
namespace Slab\Router;

class Pattern
{
    /**
     * Variable mapping from the regular expression
     *
     * @var array
     */
    private $variableMapping = array();

    /**
     * Calculated regular expression string
     *
     * @var string
     */
    private $patternString;

    /**
     * Pattern string separated into segments
     *
     * @var string
     */
    private $segments;

    /**
     * Number of required segments
     *
     * @var integer
     */
    private $requiredSegments = 0;

    /**
     * Validation Segments
     *
     * @var Validators\Base[]
     */
    private $validations = array();

    /**
     * @var array
     */
    private $validatedPassedData = [];

    /**
     * Pattern constructor
     * @param string $path
     * @param string $routePattern
     */
    public function __construct($path, $routePattern)
    {
        $this->constructInternalRegularExpression($path, $routePattern);

        $this->segments = $this->urlSegmentsFromPath($routePattern);
    }

    /**
     * @param $path
     * @param string $urlSegment
     * @return array
     */
    private function urlSegmentsFromPath($path, $urlSegment = '/')
    {
        if ($path == $urlSegment || empty($path)) return array();

        if ($path[0] == $urlSegment) {
            $path = mb_substr($path, 1);
        }

        if ($path[mb_strlen($path) - 1] == $urlSegment) {
            $path = mb_substr($path, 0, mb_strlen($path) - 1);
        }

        if (empty($path)) return array();

        $segments = array_filter(explode($urlSegment, $path));

        return $segments;
    }

    /**
     * Construct the internal pattern to test the urls against
     *
     * @param string $path
     * @param string $routePattern
     * @return boolean
     */
    private function constructInternalRegularExpression($path, $routePattern)
    {
        if (empty($routePattern)) return false;

        $fullRoute = $path;

        if (substr($fullRoute, -1) == '/') {
            $fullRoute = substr($fullRoute, 0, -1);
        }

        $fullRoute .= $routePattern;

        $patternSegments = $this->urlSegmentsFromPath($fullRoute);

        $variableNumber = 0;
        $hasOptional = false;
        $lastSegmentRequired = true;

        foreach ($patternSegments as $pattern) {
            $variableNumber++;

            $isOptionalPattern = $this->stringIsWrapped($pattern, '[', ']');
            $isDynamicPattern = $this->stringIsWrapped($pattern, '{', '}');

            if (!$isDynamicPattern) {
                $validatorClass = new \Slab\Router\Validators\Value(); //@todo findClass

                $validatorClass->setChallenge($pattern);

                if ($isOptionalPattern)
                {
                    // Can't have optional value validators
                    //$validatorClass->setOptional(true);
                }
                else
                {
                    $this->requiredSegments++;
                }

                $this->validations[] = $validatorClass;

                continue;
            }

            if (!$isOptionalPattern && $hasOptional)
            {
                //@todo log this "Route " . $path . " has required fields after optional fields.", "Router";
                return false;
            }

            list($validatorClass, $variableName) = explode(':', $pattern);

            $validatorClass = ucfirst($validatorClass);

            if (strcasecmp($validatorClass, 'String') == 0)
            {
                $validatorClass = 'Text';
            }

            $validatorClassName = '\Slab\Router\Validators\\' . $validatorClass; //@todo findClass
            if (!class_exists($validatorClassName))
            {
                //"Route " . $path . " specified invalid validator class " . $validatorClass, "Router"
                //@todo log this
                return false;
            }


            $validatorClass = new $validatorClassName;

            $this->validations[] = $validatorClass;

            $this->variableMapping[$variableNumber] = $variableName;

            if (!$isOptionalPattern)
            {
                ++$this->requiredSegments;
            }
        }

        return true;
    }

    /**
     * Tests a path against a pattern
     *
     * @param string $path
     * @param \stdClass $parameters
     * @param array $debugInformation
     * @return boolean
     */
    public function testPattern($path, $parameters, &$debugInformation)
    {
        if (is_string($path)) {
            if ($path[0] == '/') $path = substr($path, 1);

            $path = explode('/', $path);
        }

        $validatedPassedData = [];

        //Validate each segment and assign parameters to route
        $numberOfValidations = 0;
        foreach ($path as $index => $segment) {
            if (empty($this->validations[$index])) {
                if (is_array($debugInformation)) $debugInformation[] = "No validator for segment " . $index . ", failing route.";

                return false;
            }

            //Do the actual validation of the segment
            if (!$this->validations[$index]->validate($segment)) {
                if (is_array($debugInformation)) $debugInformation[] = "Failed validation check for segment '" . $segment . "' in validator " . $index;
                return false;
            }

            $validatedPassedData = array_merge($validatedPassedData, $this->validations[$index]->getOutput());

            if (is_array($debugInformation)) $debugInformation[] = "Segment '" . $segment . "' passed validation " . $index;

            //Set the parameter value if it hasn't been specified yet
            if (!empty($this->variableMapping[$index + 1])) {
                //Allow the validator to change the value (for optimizations and whatnot)
                $newValue = $this->validations[$index]->didValueChange();
                if (empty($newValue)) $newValue = $segment;

                $parameters->{$this->variableMapping[$index + 1]} = $newValue;
            }

            ++$numberOfValidations;
        }

        if ($numberOfValidations < $this->requiredSegments) {
            if (is_array($debugInformation)) $debugInformation[] = "Path too short to be valid, failing route.";

            return false;
        }

        if (is_array($debugInformation)) $debugInformation[] = "Fully validated!";

        $this->validatedPassedData = $validatedPassedData;

        return true;
    }

    /**
     * Returns true if a string is wrapped by $specialCharacterLeft and $specialCharacterRight, inputString is returned without them
     *
     * @param string $inputString
     * @param string $specialCharacterLeft
     * @param string $specialCharacterRight
     * @return bool
     */
    private function stringIsWrapped(&$inputString, $specialCharacterLeft = '[', $specialCharacterRight = ']')
    {
        $isWrapped = ($inputString[0] == $specialCharacterLeft && $inputString[mb_strlen($inputString) - 1] == $specialCharacterRight);

        $inputString = str_replace(array($specialCharacterLeft, $specialCharacterRight), '', $inputString);

        return $isWrapped;
    }

    /**
     * Returns the pattern string
     *
     * @return string
     */
    public function getPatternString()
    {
        return $this->patternString;
    }

    /**
     * Get validated data
     *
     * @return array
     */
    public function getValidatedData()
    {
        return $this->validatedPassedData;
    }
}