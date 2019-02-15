<?php

namespace Kodiak\Core\Router;


interface RouterInterface
{
    const NOT_FOUND = 0;
    const FOUND = 1;
    const METHOD_NOT_ALLOWED = 2;

    /**
     * @param array $routes
     */
    public function setRoutes(array $routes);

    /**
     * @param RouterHook $hook
     * @return void
     */
    public function addHook(RouterHook $hook);

    /**
     * @return array
     */
    public function getRoutes();

    /**
     * @param string $method HTTP method
     * @param string $uri URI
     * @return array
     */
    public function findRoute(string $method, string $uri);

    /**
     * @return array
     */
    public function getActualRoute();

}