<?php

namespace Kodiak\Request;


class Request
{
    /**
     * @var Request
     */
    protected static $instance = null;

    /**
     * @var string
     */
    private $moduleClassName;

    /**
     * @var string
     */
    private $handlerController;

    /**
     * @var string
     */
    private $handlerMethod;

    /**
     * @var array
     */
    private $handlerParameters;

    /**
     * @var array
     */
    private $additionalParameters;

    /**
     * Request constructor.
     */
    protected function __construct()
    {
        $this->additionalParameters = [];
    }

    /**
     * @return Request
     */
    public static function get(): Request {
        if(!self::$instance){
            self::$instance = new Request();
        }
        return self::$instance;
    }

    /**
     * @return string
     */
    public function getHandlerController(): string
    {
        return $this->handlerController;
    }

    /**
     * @param string $handlerController
     */
    public function setHandlerController(string $handlerController)
    {
        $this->handlerController = $handlerController;
    }

    /**
     * @return string
     */
    public function getHandlerMethod(): string
    {
        return $this->handlerMethod;
    }

    /**
     * @param string $handlerMethod
     */
    public function setHandlerMethod(string $handlerMethod)
    {
        $this->handlerMethod = $handlerMethod;
    }

    /**
     * @return array
     */
    public function getHandlerParameters(): array
    {
        return $this->handlerParameters;
    }

    /**
     * @param string $handlerParameters
     */
    public function setHandlerParameters(string $handlerParameters)
    {
        $this->handlerParameters = $handlerParameters;
    }

    /**
     * @return string
     */
    public function getModuleClassName(): string
    {
        return $this->moduleClassName;
    }

    /**
     * @param string $moduleClassName
     */
    public function setModuleClassName(string $moduleClassName)
    {
        $this->moduleClassName = $moduleClassName;
    }

    public function getHttpMethod(): string {
        return $_SERVER['REQUEST_METHOD'];
    }

    public function getUri(): string {
        return $_SERVER['REQUEST_URI'];
    }

    /**
     * @param array $additionalParameters
     */
    public function setAdditionalParameters(array $additionalParameters)
    {
        $this->additionalParameters = $additionalParameters;
    }

    public function getAdditionalParameter($parameterName)
    {
        return isset($this->additionalParameters[$parameterName]) ? $this->additionalParameters[$parameterName] : null;
    }
}