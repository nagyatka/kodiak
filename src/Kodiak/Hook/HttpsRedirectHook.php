<?php

namespace Kodiak\Hook;


use Kodiak\Core\KodiConf;
use Kodiak\Exception\RedirectException;
use Kodiak\Request\Request;

class HttpsRedirectHook extends  HookInterface
{
    /**
     * @param KodiConf $conf
     * @param Request $request
     * @return Request
     * @throws RedirectException
     */
    public function process(KodiConf $conf, Request $request): Request
    {
        $redirect = "https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        throw new RedirectException($redirect);
    }
}