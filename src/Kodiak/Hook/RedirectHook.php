<?php

namespace Kodiak\Hook;


use Kodiak\Core\KodiConf;
use Kodiak\Exception\RedirectException;
use Kodiak\Request\Request;

class RedirectHook extends HookInterface
{
    /**
     * Used parameter name: redirect_url
     *
     * @param KodiConf $kodiConf
     * @param Request $request
     * @return Request
     * @throws RedirectException
     */
    public function process(KodiConf $kodiConf, Request $request): Request
    {
        throw new RedirectException($this->getParameterByKey("redirect_url"));
    }
}