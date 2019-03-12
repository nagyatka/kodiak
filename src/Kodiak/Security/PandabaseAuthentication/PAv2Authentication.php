<?php

namespace Kodiak\Security\PandabaseAuthentication;
use Kodiak\Application;
use Kodiak\Security\Model\Authentication\AuthenticationTaskResult;
use Kodiak\Security\Model\SecurityManager;
use Kodiak\Security\Model\User\AnonymUser;
use Kodiak\Security\Model\User\AuthenticatedUserInterface;
use Kodiak\Security\Model\User\PendingUser;
use Kodiak\Security\Model\User\Role;
use PHPGangsta_GoogleAuthenticator;

/**
 * Class PAv2Authentication
 *
 * It is a Pandabase ORM based authentication implementation which uses username/password pair and a second factor
 * to authenticate a user. The 2FA implementation is using GoogleAuthenticator
 *
 * @package KodiSecurity\Model\Authentication
 */
class PAv2Authentication extends PAv1Authentication
{
    public function login(array $credentials): AuthenticationTaskResult
    {
        /** @var SecurityManager $securityManager */
        $securityManager = Application::get("security");
        $user = $securityManager->getUser();

        if($user->hasRole(Role::PENDING_USER)) {
            /** @var AuthenticatedUserInterface $userClassName */
            $userClassName = $this->getConfiguration()["user_class_name"];
            $ga = new PHPGangsta_GoogleAuthenticator();


            if($user->getUserId() < 0) {
                return new AuthenticationTaskResult(false, "A valid user id is required for 2FA.");
            }
            $pendingUser = $userClassName::getUserByUserId($user->getUserId());

            if(!isset( $credentials["code"])) {
                return new AuthenticationTaskResult(false, "Missing 2FA code.");
            }
            $code = $credentials["code"];

            if($ga->verifyCode($pendingUser->get2FASecret(), $code, 2)) {
                return new AuthenticationTaskResult(true, $pendingUser);
            }
            session_destroy();
            return new AuthenticationTaskResult(false, "Wrong 2FA code");
        }

        $loginResult = parent::login($credentials);
        if($loginResult->isSuccess()) {
            return new AuthenticationTaskResult(true, new PendingUser($loginResult->getResult()->getUserId()));
        }
        return $loginResult;
    }

}