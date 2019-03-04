<?php

namespace Kodiak\Security\Model;


use Kodiak\Application;
use Kodiak\Exception\Http\HttpAccessDeniedException;
use Kodiak\Exception\Http\HttpInternalServerErrorException;
use Kodiak\Security\Model\Authentication\AuthenticationInterface;
use Kodiak\Security\Model\Authentication\AuthenticationRequest;
use Kodiak\Security\Model\Authentication\AuthenticationTaskResult;
use Kodiak\Security\Model\User\AnonymUser;
use Kodiak\Security\Model\User\AuthenticatedUserInterface;
use Kodiak\Session\Session;

class SecurityManager
{
    const SESS_LOGGED_IN    = "logged_in";
    const SESS_UPDATED_AT   = "updated_at";
    const SESS_USER_ID      = "user_id";
    const SESS_USERNAME     = "username";
    const SESS_ROLES        = "roles";

    /**
     * @var int
     */
    private $expiration_time;

    /**
     * @var array
     */
    private $authenticationConfiguration;

    /**
     * @var AuthenticationInterface
     */
    private $authentication;

    /**
     * @var string
     */
    private $userClassName;

    /**
     * SecurityManager constructor.
     * @param $configuration
     */
    public function __construct($configuration)
    {
        $this->expiration_time              = $configuration["expiration_time"];
        $this->userClassName                = $configuration["user_class_name"];
        $this->authenticationConfiguration  = $configuration["authentication"];
        $this->authentication               = null;
    }

    /**
     * @return AuthenticatedUserInterface
     */
    public function getUser(): AuthenticatedUserInterface {

        // Get session
        $securitySession = $this->getSecuritySession();

        // If there's no security session in PHP session we assume that it is an AnonymUser
        if(!$securitySession) {
            return new AnonymUser();
        }

        // Status and expiration time check
        if($securitySession[self::SESS_LOGGED_IN] === true &&
            (time() - $securitySession[self::SESS_UPDATED_AT] > $this->expiration_time)) {
            $newUser = new AnonymUser();
            $this->setSecuritySession(false, time(), $newUser->getUserId(), $newUser->getUsername(), $newUser->getRoles());
            return $newUser;
        }
        else {
            /** @var AuthenticatedUserInterface $userClassName */
            $userClassName = $this->userClassName;
            return $userClassName::getUserFromSecuritySession(
                $securitySession[self::SESS_USER_ID],
                $securitySession[self::SESS_USERNAME],
                $securitySession[self::SESS_ROLES]
            );
        }
    }

    /**
     * @return int
     */
    public function getUserId(): ?int {
        // Get session
        $securitySession = $this->getSecuritySession();

        // If there's no security session in PHP session we assume that it is an AnonymUser
        if(!$securitySession) {
            return -1;
        }

        // Status and expiration time check
        if($securitySession[self::SESS_LOGGED_IN] === true &&
            (time() - $securitySession[self::SESS_UPDATED_AT] > $this->expiration_time)) {
            $newUser = new AnonymUser();
            $this->setSecuritySession(false, time(), $newUser->getUserId(), $newUser->getUsername(), $newUser->getRoles());
            return -1;
        }
        else {
            return  $securitySession[self::SESS_USER_ID];
        }
    }

    /**
     * @param AuthenticationRequest $request
     * @return AuthenticationTaskResult
     * @throws HttpAccessDeniedException
     */
    public function handleAuthenticationRequest(AuthenticationRequest $request): AuthenticationTaskResult {
        $authentication = $this->getAuthenticationInterface();
        switch ($request->getType()) {
            /*
             * Handle login request
             */
            case AuthenticationRequest::REQ_LOGIN:
                $authenticationResult = $authentication->login($request->getCredentials());
                if($authenticationResult->isSuccess()) {
                    /** @var AuthenticatedUserInterface $user */
                    $user = $authenticationResult->getResult();
                    // Save variables in session
                    $this->setSecuritySession(true, time(), $user->getUserId(), $user->getUsername(), $user->getRoles());
                }
                return $authenticationResult;

            /*
             * Handle register request
             */
            case AuthenticationRequest::REQ_REGISTER:

                if($this->getSecuritySession()[self::SESS_LOGGED_IN]) {
                    throw new HttpAccessDeniedException("ALREADY_LOGGED_IN");
                }
                $authenticationResult = $authentication->register($request->getCredentials());
                return $authenticationResult;

            /*
             * Handle deregister request
             */
            case AuthenticationRequest::REQ_DEREGISTER:
                $authenticationResult = $authentication->deRegister($request->getCredentials());
                if($authenticationResult->isSuccess()) {
                    $this->eraseSecuritySession();
                    return $authenticationResult;
                }
                else {
                    throw new HttpAccessDeniedException();
                }
                break;

            /*
             * Handle logout request
             */
            case AuthenticationRequest::REQ_LOGOUT:
                $this->eraseSecuritySession();
                return new AuthenticationTaskResult(true, null);

            /*
             * Handle change password request
             */
            case AuthenticationRequest::REQ_CHANGE_PASS:
                $authenticationResult = $authentication->changePassword($request->getCredentials());
                if($authenticationResult->isSuccess()) {
                    return $authenticationResult;
                }
                else {
                    throw new HttpAccessDeniedException();
                }
                break;

            /*
             * Handle reset password request
             */
            case AuthenticationRequest::REQ_RESET_PASS:
                $authenticationResult = $authentication->resetPassword($request->getCredentials());
                if($authenticationResult->isSuccess()) {
                    return $authenticationResult;
                }
                else {
                    throw new HttpAccessDeniedException();
                }
                break;

            /*
             * Throws AccessDeniedException as default
             */
            default:
                throw new HttpAccessDeniedException();
        }
    }

    /**
     * @return array|null
     */
    private function getSecuritySession(): ?array {
        $session = Application::get("session");
        return $session->get("security", null);
    }

    /**
     * @param array $securitySession
     */
    private function setSecuritySessionFromArray(array $securitySession) {
        /** @var Session $session */
        $session = Application::get("session");
        if($session) {
            $session->set("security", $securitySession);
        }
    }

    /**
     * @param bool $logged_in
     * @param int $updated_at
     * @param int $user_id
     * @param string $username
     * @param array $roles
     * @throws HttpInternalServerErrorException
     */
    private function setSecuritySession(bool $logged_in, int $updated_at, ?int $user_id, ?string $username, ?array $roles) {
        /** @var Session $session */
        $session = Application::get("session");
        if($session) {
            $session->set("security", [
                self::SESS_LOGGED_IN    => $logged_in,
                self::SESS_UPDATED_AT   => $updated_at,
                self::SESS_USER_ID      => $user_id,
                self::SESS_USERNAME     => $username,
                self::SESS_ROLES        => $roles
            ]);
        } else {
            throw new HttpInternalServerErrorException("MISSING_SESSION");
        }
    }

    /**
     *
     */
    private function eraseSecuritySession(): void {
        session_destroy();
        //$newUser = new AnonymUser();
        //$this->setSecuritySession(false, time(), $newUser->getUserId(), $newUser->getUsername(), $newUser->getRoles());
    }

    /**
     * @return AuthenticationInterface
     */
    private function getAuthenticationInterface(): AuthenticationInterface {
        if(!$this->authentication) {
            $authenticationClassName = $this->authenticationConfiguration["class_name"];
            $params = $this->authenticationConfiguration["parameters"];
            $params = array_merge($params,[
                "user_class_name" => $this->userClassName
            ]);
            $this->authentication = new $authenticationClassName($params);
        }
        return $this->authentication;
    }

    /**
     * It refreshes the update_at session variable to actual time with time() function call.
     *
     */
    public function refreshUpdateAt() {
        // Get session
        $securitySession = $this->getSecuritySession();
        if($securitySession != null) {
            $securitySession[self::SESS_UPDATED_AT] = time();
            $this->setSecuritySessionFromArray($securitySession);
        }
    }

    /**
     * @return int
     */
    public function getExpirationTime(): int
    {
        return $this->expiration_time;
    }


}