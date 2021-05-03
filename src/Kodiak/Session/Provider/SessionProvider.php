<?php

namespace Kodiak\Session\Provider;

use Kodiak\ServiceProvider\TwigProvider\Twig;
use Kodiak\Session\Session;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class SessionProvider implements ServiceProviderInterface
{

    public function register(Container $pimple)
    {
        $pimple["session"] = $pimple->factory(function(){
            return new Session();
        });

        $pimple->extend('twig', function ($twig, $c) {
            /** @var Twig $mytwig */
            $mytwig = $twig;
            /** @var Session $session */
            $session = $c["session"];
            $getSessionVar = new \Twig\TwigFunction("getSessionVar",function($variable) use ($session) {
                return $session->get($variable);
            });
            $mytwig->getTwigEnvironment()->addFunction($getSessionVar);
            return $mytwig;
        });        
    }
}
