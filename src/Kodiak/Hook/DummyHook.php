<?php

namespace Kodiak\Hook;


use Kodiak\Core\KodiConf;
use Kodiak\Request\Request;

class DummyHook extends HookInterface
{
    /**
     * @param KodiConf $conf
     * @param Request $request
     * @return Request
     */
    public function process(KodiConf $conf, Request $request): Request
    {
        return $request;
    }
}