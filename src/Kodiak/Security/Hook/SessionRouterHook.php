<?php

namespace Kodiak\Security\Hook;


use Kodiak\Application;
use Kodiak\Core\Router\RouterHook;
use Kodiak\Security\Model\SecurityManager;

class SessionRouterHook implements RouterHook
{
    /**
     * The SessionRouterHook will update the session time if the session_update route parameter is set and it is true.
     *
     * @param $actual_route
     * @return mixed|void
     */
    public function run($actual_route)
    {
        $session_update = true;
        if(isset($actual_route["session_update"])) {
            $session_update = $actual_route["session_update"];
        }

        if($session_update) {
            /** @var SecurityManager $securityManager */
            $securityManager = Application::get("security");
            $securityManager->refreshUpdateAt();
        }

    }
}