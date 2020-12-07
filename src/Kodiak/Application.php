<?php
namespace Kodiak;


use Kodiak\Core\Core;
use Kodiak\Core\KodiConf;
use Kodiak\Exception\ConfigurationException;
use Kodiak\Exception\CoreException;
use Kodiak\Exception\Http\HttpAccessDeniedException;
use Kodiak\Exception\Http\HttpAuthRequiredException;
use Kodiak\Exception\Http\HttpInternalServerErrorException;
use Kodiak\Exception\Http\HttpNotFoundException;
use Kodiak\Exception\Http\HttpServiceTemporarilyUnavailableException;
use Kodiak\Exception\RedirectException;
use Kodiak\Hook\ResponseHookInterface;
use Kodiak\Request\CronRequest;
use Kodiak\Request\Request;
use Pimple\Container;
use Pimple\Exception\UnknownIdentifierException;

class Application implements \ArrayAccess
{
    /**
     * Singleton minta
     *
     * @var Application
     */
    private static $instance = null;

    /**
     * @var Core
     */
    private $core;

    /**
     * @var KodiConf
     */
    private $kodiConfiguration;

    /**
     * @var Container
     */
    private $pimpleContainer;

    /**
     * @return Application
     */
    public static function getInstance() {
        if(Application::$instance == null) {
            Application::$instance = new Application();
        }
        return Application::$instance;
    }

    /**
     * @return string
     */
    public static function getEnvMode(): string {
        return self::get("environment")["mode"];
    }

    /**
     * @param $key
     * @return mixed
     */
    public static function get($key) {
        try {
            return Application::getInstance()[$key];
        }
        catch(UnknownIdentifierException $exception) {
            return null;
        }
    }

    /**
     * @param string $key
     * @return mixed
     */
    public static function getEnv(string $key) {
        return self::getInstance()["environment"][$key];
    }

    /**
     * Application constructor.
     */
    protected function __construct()
    {
        $this->pimpleContainer = new Container();
    }

    /**
     * @param array $conf
     * @param bool $cmd_line
     * @throws ConfigurationException
     */
    public function run(array $conf, $cmd_line = false): void {
        if (!$cmd_line) {
            ob_start();
        }
        $kodiConf = new KodiConf($conf);
        $request = $cmd_line ? CronRequest::get() : Request::get();
        $response = null;
        try {

            // Init application (init services and environment) using KodiConf
            $this->initializeConfiguration($kodiConf);

            // Process request
            $this->core = new Core($this);
            $module = $this->core->processRequest($kodiConf,$request);

            // Run the appropriate module and print result
            $response = $module->run();

        }
        catch (\Throwable $exception) {
            $errorHandler = $kodiConf->getErrorResponseHandler();
            if ($exception instanceof HttpAuthRequiredException) {
                $response = $errorHandler->error_401($request, $exception);
            }
            elseif ($exception instanceof HttpAccessDeniedException) {
                $response = $errorHandler->error_403($request, $exception);
            }
            elseif($exception instanceof HttpNotFoundException) {
                $response = $errorHandler->error_404($request, $exception);
            }
            elseif ($exception instanceof HttpInternalServerErrorException) {
                $response = $errorHandler->error_500($request, $exception);
            }
            elseif ($exception instanceof HttpServiceTemporarilyUnavailableException) {
                $response = $errorHandler->error_503($request, $exception);
            }
            elseif ($exception instanceof RedirectException) {
                $redirect = $exception->getRedirectUrl();
                header("Location:$redirect");
            }
            else {
                $response = $errorHandler->custom_error($request, $exception);
            }
        }
        finally {

            $responseHookConfiguration = $this->getKodiConf()->getResponseHookConfiguration();
            foreach ($responseHookConfiguration as $hook) {
                /** @var ResponseHookInterface $r_hook */
                $r_hook = null;
                if (is_string($hook)) {
                    $r_hook = new $hook();
                }
                elseif (is_array($hook)) {
                    $hookClassName  = $hook["class_name"];
                    $hookParameters = $hook["parameters"];
                    if(!$hookParameters) $hookParameters = [];
                    $r_hook = new $hookClassName($hookParameters);
                }
                //else {
                   // Log unknown config
                //}
                $r_hook->process($this->getKodiConf(), $request, $response);
            }

            print $response;

            if (!$cmd_line) {
                ob_end_flush();
            }
        }
    }

    /**
     * @param KodiConf $kodiConf
     * @throws ConfigurationException
     */
    private function initializeConfiguration(KodiConf $kodiConf): void {
        $this->kodiConfiguration                = $kodiConf;
        $this->pimpleContainer["environment"]   = $kodiConf->getEnvironmentSettings();
        foreach ($kodiConf->getServicesConfiguration() as $service) {
            if (is_string($service)) {
                $this->pimpleContainer->register(new $service());
            }
            elseif (is_array($service)) {
                $serviceClassName  = $service["class_name"];
                $serviceParameters = $service["parameters"];
                $this->pimpleContainer->register(new $serviceClassName($serviceParameters));
            }
            else {
                throw new ConfigurationException("Unknown in configuration.");
            }
        }
    }

    /**
     * @return KodiConf
     */
    public function getKodiConf(): KodiConf {
        return $this->kodiConfiguration;
    }

    public function offsetExists($offset)
    {
        return isset($this->pimpleContainer[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->pimpleContainer[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->pimpleContainer[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->pimpleContainer[$offset]);
    }

    /**
     * @return Core
     */
    public function getCore(): Core
    {
        return $this->core;
    }
}