<?php


namespace Kodiak\Hook;


use Kodiak\Application;
use Kodiak\Core\KodiConf;
use Kodiak\Request\Request;
use Kodiak\Session\Session;

class SessionTokenHook extends HookInterface
{

    public function process(KodiConf $kodiConf, Request $request): Request
    {
        /** @var Session $session */
        $session = Application::get("session");

        if($session->get("sessionToken") == null) {
            $token = bin2hex(openssl_random_pseudo_bytes(64));
            $session->set('sessionToken', $token);
        }
        return $request;
    }
}