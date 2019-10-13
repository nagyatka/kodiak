<?php


namespace Kodiak\Core;

use Kodiak\Response\Response;

class RequestHandler
{
    /**
     * @var string
     */
    private $controllerName;

    /**
     * @var string
     */
    private $method;

    /**
     * @var array
     */
    private $urlParams;

    /**
     * @return Response
     */
    public function run(): Response
    {
        $controllerFullName = $this->controllerName;
        $controller = new $controllerFullName();
        $result = $controller->{$this->method}($this->urlParams);
        if (is_string($result) || $result == null) {
            $result = new Response($result);
        }
        return $result;
    }


    /**
     * @param string $method
     */
    public function setMethod(string $method)
    {
        $this->method = $method;
    }

    /**
     * @param array $urlParams
     */
    public function setUrlParams(array $urlParams)
    {
        $this->urlParams = $urlParams;
    }

    /**
     * @param string $controllerName
     */
    public function setControllerName(string $controllerName): void
    {
        $this->controllerName = $controllerName;
    }


}