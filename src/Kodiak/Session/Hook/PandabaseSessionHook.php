<?php

namespace Kodiak\Session\Hook;


use Kodiak\Session\Handler\PandabaseSessionHandler;
use Kodiak\Core\KodiConf;
use Kodiak\Hook\HookInterface;
use Kodiak\Request\Request;

class PandabaseSessionHook extends HookInterface
{

    public function process(KodiConf $kodiConf, Request $request): Request
    {
        // Gathering parameters
        $connectionName =  $this->getParameterByKey("connection_name") ?? "default";
        $options = $this->getParameterByKey("options") ?? [];

        // Set session handler
        $sessionHandler = new PandabaseSessionHandler([
            "connection_name" => $connectionName,
            "options" => $options
        ]);
        session_set_save_handler($sessionHandler);
        session_start();

        return $request;
    }
}