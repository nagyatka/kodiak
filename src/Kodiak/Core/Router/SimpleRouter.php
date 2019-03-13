<?php

namespace Kodiak\Core\Router;

use FastRoute\Dispatcher\GroupCountBased;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;
use Kodiak\Exception\Http\HttpNotFoundException;

class SimpleRouter implements RouterInterface
{
    /**
     * @var GroupCountBased
     */
    private $dispatcher;

    /**
     * @var array
     */
    private $routes;

    /** @var RouterHook[] */
    private $hooks;

    /**
     * @var array
     */
    private $actualRoute;

    /**
     * SimpleRouter constructor.
     * @param array $params
     */
    public function __construct(array $params)
    {
        $this->hooks = [];
        if(isset($params["hooks"])) {
            foreach ($params["hooks"] as $hookClassName) {
                $this->hooks[] = new $hookClassName();
            }
        }
    }


    /**
     * Paraméterek betöltése.
     *
     * A tömb struktúra:
     * [
     *      [
     *          "method"    =>  [POST|GET|PUT|DELETE],
     *          "url"       =>  "/foo/bar/1222",
     *          "handler"   =>  ClassName::methodName
     *      ],
     *      ....
     * ]
     *
     * @param array $routes
     */
    public function setRoutes(array $routes)
    {
        $this->routes = $routes;
    }

    /**
     * @inheritdoc
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * @param string $method
     * @param string $uri
     * @return array
     * @throws HttpNotFoundException
     */
    public function findRoute(string $method, string $uri)
    {
        $routes = $this->getRoutes();
        $this->dispatcher = simpleDispatcher(function (RouteCollector $r) use ($routes) {
            foreach ($routes as $route) {
                $r->addRoute($route["method"], $route["url"], $route["handler"]);
            }
        });

        // Strip query string (?foo=bar) and decode URI
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }
        $uri = rawurldecode($uri);

        $routeInfo = $this->dispatcher->dispatch($method, $uri);
        switch ($routeInfo[0]) {
            case RouterInterface::NOT_FOUND:
                throw new HttpNotFoundException();
            case RouterInterface::METHOD_NOT_ALLOWED:
                throw new HttpNotFoundException();
            case RouterInterface::FOUND:
                foreach ($routes as $route) {
                    if ($route["handler"] == $routeInfo[1]) {
                        $this->actualRoute = $route;
                        break;
                    }
                }
                // Runs the registered hooks
                foreach ($this->hooks as $hook) {
                    $hook->run($this->actualRoute);
                }

                return [
                    "handler" => $routeInfo[1],
                    "params" => $routeInfo[2]
                ];
            default:
                return [];
        }
    }

    /**
     * @return array
     */
    public function getActualRoute()
    {
        return $this->actualRoute;
    }

    public function addHook(RouterHook $hook)
    {
        $this->hooks[] = $hook;
    }
}