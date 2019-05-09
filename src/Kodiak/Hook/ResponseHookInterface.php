<?php


namespace Kodiak\Hook;


use Kodiak\Core\KodiConf;
use Kodiak\Request\Request;
use Kodiak\Response\Response;

abstract class ResponseHookInterface
{
    /**
     * @var array
     */
    private $parameters;

    /**
     * HookInterface constructor.
     * @param array $parameters
     */
    public function __construct(array $parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * @param KodiConf $kodiConf
     * @param Request $request
     * @param Response $response
     * @return Request
     */
    abstract public function process(KodiConf $kodiConf, Request $request, Response $response);

    /**
     * @param string $key
     * @return mixed
     */
    public function getParameterByKey(string $key) {
        if(!isset($this->parameters[$key])) return null;
        return $this->parameters[$key];
    }
}