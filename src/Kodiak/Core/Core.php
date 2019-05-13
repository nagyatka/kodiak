<?php

namespace Kodiak\Core;


use Kodiak\Application;
use Kodiak\Core\Module\Module;
use Kodiak\Core\Module\ModuleParams;
use Kodiak\Core\Router\RouterInterface;
use Kodiak\Core\Router\SimpleRouter;
use Kodiak\Exception\CoreException;
use Kodiak\Hook\HookInterface;
use Kodiak\Request\Request;

class Core
{
    /**
     * Registered hooks in the core
     * @var HookInterface[]
     */
    private $registeredHooks;

    /**
     * @var RouterInterface
     */
    private $router = null;

    /**
     * Core constructor.
     * @param Application $application
     * @throws CoreException
     * @throws \Kodiak\Exception\ConfigurationException
     */
    public function __construct(Application $application)
    {
        // Get configuration
        $hookConfiguration = $application->getKodiConf()->getHooksConfiguration();

        // Hook registration
        $this->registeredHooks = [];
        foreach ($hookConfiguration as $hook) {
            if (is_string($hook)) {
                $this->registeredHooks[] = new $hook();
            }
            elseif (is_array($hook)) {
                $hookClassName  = $hook["class_name"];
                $hookParameters = $hook["parameters"];
                if(!$hookParameters) $hookParameters = [];
                $this->registeredHooks[] = new $hookClassName($hookParameters);
            }
            else {
                throw new CoreException("Unknown hook in configuration.");
            }
        }
    }

    /**
     * @param KodiConf $kodiConf
     * @param Request $request
     * @return Module
     * @throws \Kodiak\Exception\ConfigurationException
     * @throws \Kodiak\Exception\Http\HttpNotFoundException
     */
    public function processRequest(KodiConf $kodiConf, Request $request): Module {

        // Init router
        $routerConfiguration = $kodiConf->getRouterConfiguration();
        if(!isset($routerConfiguration["class_name"])) {
            $this->router = new SimpleRouter([]);
        }
        else {
            $routerClassName = $routerConfiguration["class_name"];
            if(!isset($routerConfiguration["parameters"])) $routerConfiguration["parameters"] = [];
            /** @var RouterInterface $router */
            $this->router = new $routerClassName($routerConfiguration["parameters"]);
        }
        $this->router->setRoutes($kodiConf->getRoutesConfiguration());

        // Run hooks
        foreach ($this->registeredHooks as $registeredHook) {
            /** @var HookInterface $registeredHook */
            $request = $registeredHook->process($kodiConf, $request);
        }

        // Run router
        $routerResult = $this->router->findRoute($request->getHttpMethod(), $request->getUri());
        $parts = $controllerParts = explode("::", $routerResult["handler"]);

        $request->setHandlerController($parts[1]);
        $request->setHandlerMethod($parts[2]);

        // Add existing additional parameters
        $request->setAdditionalParameters(array_diff_assoc($this->router->getActualRoute(), [
            "method"    => null,
            "url"       => null,
            "handler"   => null,
        ]));

        // Initialize module
        $moduleClassName = $parts[0];
        /** @var Module $module */
        $module = new $moduleClassName();
        $module->setControllerName($parts[1]);
        $module->setMethod($parts[2]);
        $module->setUrlParams($routerResult["params"]);
        if(isset($kodiConf->getModulesConfiguration()[$moduleClassName])) {
            ModuleParams::setParams($kodiConf->getModulesConfiguration()[$moduleClassName]);
        }
        $module->before();

        return $module;
    }

    /**
     * @return RouterInterface
     */
    public function getRouter(): RouterInterface
    {
        return $this->router;
    }


}