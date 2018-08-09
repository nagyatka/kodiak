<?php

namespace Kodiak\Session\Provider;

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
    }
}