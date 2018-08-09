<?php

namespace Kodiak\Security\Hook;


use Kodiak\Application;
use Kodiak\Core\KodiConf;
use Kodiak\Exception\Http\HttpAccessDeniedException;
use Kodiak\Hook\HookInterface;
use Kodiak\Request\Request;
use Kodiak\Security\Model\SecurityManager;
use Kodiak\Security\Model\User\AuthenticatedUserInterface;
use Kodiak\Security\Model\User\Role;

/**
 * Class FirewallHook
 *
 * The FireWallHook checks that the actual user (or non-authenticated user) has permission to get the response for the
 * request. If not it will throw an HttpAccessDeniedException.
 *
 * IMPORTANT: For security reasons, if the permission checker does not find a registry for the uri it will always deny the
 * access!
 *
 * @package KodiSecurity\Hook
 */
class FirewallHook extends HookInterface
{
    public function process(KodiConf $kodiConf, Request $request): Request
    {
        /** @var SecurityManager $securityManager */
        $securityManager = Application::get("security");

        /*
         * The getUser instance method checks that the user's session is expired or not. If it is, it will throw
         * an HttpAccessDeniedException.
         */
        if(!$this->hasPermission($securityManager->getUser(),$request->getUri())) {
            throw new HttpAccessDeniedException();
        }
        return $request;
    }

    /**
     * Checks that the user has permission to get response for the request.
     *
     * @param AuthenticatedUserInterface $user
     * @param string $uri
     * @return bool
     */
    private function hasPermission(AuthenticatedUserInterface $user, string $uri) {

        // Collect permissions
        $permissions = $this->getParameterByKey("permissions");

        // Iterate over permission
        foreach ($permissions as $permissionPath => $acceptableRoles) {
            if($permissionPath[0] !== "/") {
                $permissionPath = "/".$permissionPath;
            }
            if(substr($permissionPath,-1) !== "/") {
                $permissionPath = $permissionPath."/";
            }
            $match = preg_match($permissionPath,$uri);
            if ($match == 1) {
                if(in_array(Role::ANON_USER,$acceptableRoles)) return true;
                foreach ($acceptableRoles as $permissionRole) {
                    if(in_array($permissionRole,$user->getRoles())) return true;
                }
                return false;
            }
        }
        return false;
    }
}