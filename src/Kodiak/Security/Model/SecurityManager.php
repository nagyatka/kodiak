<?php

namespace Kodiak\Security\Model;


use Kodiak\Application;
use Kodiak\Exception\Http\HttpAccessDeniedException;
use Kodiak\Exception\Http\HttpInternalServerErrorException;
use Kodiak\Security\Model\Authentication\AuthenticationInterface;
use Kodiak\Security\Model\Authentication\AuthenticationMode;
use Kodiak\Security\Model\Authentication\AuthenticationRequest;
use Kodiak\Security\Model\Authentication\AuthenticationTaskResult;
use Kodiak\Security\Model\Authentication\AuthSelectors;
use Kodiak\Security\Model\User\AnonymUser;
use Kodiak\Security\Model\User\AuthenticatedUserInterface;
use Kodiak\Security\Model\User\PendingUser;
use Kodiak\Security\Model\User\Role;
use Kodiak\Session\Session;

class SecurityManager
{
    const SESS_LOGGED_IN    = "logged_in";
    const SESS_UPDATED_AT   = "updated_at";
    const SESS_USER_ID      = "user_id";
    const SESS_USERNAME     = "username";
    const SESS_ROLES        = "roles";
    const SESS_IS_PENDING   = "is_pending";

    /**
     * @var int
     */
    private $expiration_time;

    /**
     * @var AuthenticationMode[]
     */
    private $authentication_modes;

    /**
     * @var string
     */
    private $auth_selector;

    /**
     * @var string
     */
    private $user_class;

    /**
     * SecurityManager constructor.
     * @param $configuration
     */
    public function __construct($configuration)
    {
        $this->expiration_time      = $configuration["sess_exp_time"];
        $this->authentication_modes = [];
        foreach ($configuration["auth_modes"] as $auth_mode) {
            $class_name = $auth_mode["class_name"];
            $parameters = $auth_mode["parameters"];
            /** @var AuthenticationMode $mode */
            $mode = new $class_name($parameters);
            $this->authentication_modes[$mode::name()] = $mode;
        }
        $this->auth_selector = AuthSelectors::get($configuration["auth_selector"]);
        $this->user_class = $configuration["user_class"];
    }

    /**
     * @return AuthenticatedUserInterface
     * @throws HttpInternalServerErrorException
     */
    public function getUser(): AuthenticatedUserInterface {

        // Get session
        $securitySession = $this->getSecuritySession();

        // If there's no security session in PHP session we assume that it is an AnonymUser
        if(!$securitySession) {
            return new AnonymUser();
        }


        // If it is a pending user
        if(!isset($securitySession[self::SESS_LOGGED_IN]) || $securitySession[self::SESS_LOGGED_IN] === false) {
            return new AnonymUser();
        }
        elseif($securitySession[self::SESS_LOGGED_IN] === true && $securitySession[self::SESS_IS_PENDING] === true) {
            return new PendingUser($securitySession[self::SESS_USER_ID]);
        }
        // Status and expiration time check
        elseif($securitySession[self::SESS_LOGGED_IN] === true &&
            (time() - $securitySession[self::SESS_UPDATED_AT] > $this->expiration_time)) {
            $newUser = new AnonymUser();
            $this->setSecuritySession(false, time(), $newUser->getUserId(), $newUser->getUsername(), $newUser->getRoles());
            return $newUser;
        }
        else {
            /** @var AuthenticatedUserInterface $userClassName */
            $userClassName = $this->getUserClass();
            return $userClassName::getUserFromSecuritySession(
                $securitySession[self::SESS_USER_ID],
                $securitySession[self::SESS_USERNAME],
                $securitySession[self::SESS_ROLES]
            );
        }
    }

    /**
     * @return int
     * @throws HttpInternalServerErrorException
     */
    public function getUserId(): ?int {
        // Get session
        $securitySession = $this->getSecuritySession();

        // If there's no security session in PHP session we assume that it is an AnonymUser
        if(!$securitySession) {
            return -1;
        }

        // If it is a pending user
        if(!isset($securitySession[self::SESS_LOGGED_IN]) || $securitySession[self::SESS_LOGGED_IN] === false) {
            return -1;
        }
        elseif($securitySession[self::SESS_LOGGED_IN] === true && $securitySession[self::SESS_IS_PENDING] === true) {
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
     * @throws HttpInternalServerErrorException
     */
    public function handleAuthenticationRequest(AuthenticationRequest $request): AuthenticationTaskResult {

        // Elpször ki kell választani, hogy milyen authentikációs móddal tudunk működni.
        // Ehhez kell egy username, amit vagy az aktuális requestből lehet begyűjteni
        if(array_key_exists(self::SESS_USERNAME, $request->getCredentials())) {
            $username = $request->getCredentials()[self::SESS_USERNAME];
        }
        // Vagy az aktuális sessionből. Előfordulhat, hogy a username null
        else {
            $username = $this->getUser()->getUsername();
        }
        $authentication = $this->getAuthenticationInterface($username);

        switch ($request->getType()) {
            /*
             * Handle login request
             */
            case AuthenticationRequest::REQ_LOGIN:
                $authenticationResult = $authentication->login($request->getCredentials());
                if($authenticationResult->isSuccess()) {
                    /** @var AuthenticatedUserInterface $user */
                    $user = $authenticationResult->getResult();
                    $is_pending = $user->hasRole(Role::PENDING_USER);
                    // Save variables in session
                    $this->setSecuritySession(true, time(), $user->getUserId(), $user->getUsername(),
                        $user->getRoles(), $is_pending);
                    session_regenerate_id();
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
                    throw new HttpAccessDeniedException($authenticationResult->getResult());
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
     * @param bool $is_pending
     * @throws HttpInternalServerErrorException
     */
    private function setSecuritySession(bool $logged_in, int $updated_at, ?int $user_id, ?string $username,
                                        ?array $roles, $is_pending = false) {
        /** @var Session $session */
        $session = Application::get("session");
        if($session) {
            $session->set("security", [
                self::SESS_LOGGED_IN    => $logged_in,
                self::SESS_UPDATED_AT   => $updated_at,
                self::SESS_USER_ID      => $user_id,
                self::SESS_USERNAME     => $username,
                self::SESS_ROLES        => $roles,
                self::SESS_IS_PENDING   => $is_pending
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
    }

    /**
     * @throws HttpInternalServerErrorException
     */
    public function resetSecuritySessionVariables() {
        $this->setSecuritySession(false, time(), null, null, null, false);
    }

    /**
     * @param string $username
     * @return AuthenticationInterface
     */
    private function getAuthenticationInterface($username): AuthenticationInterface {
        return $this->getAuthMode($username)->getAuthenticationInterface();
    }

    /**
     * Visszaadja az aktuálisan érvényes authentikációs módokat.
     *
     * @return array
     */
    public function getAuthModes() {
        return array_keys($this->authentication_modes);
    }

    /**
     * Visszaadja a paraméterben kapott felhasználóhoz érvényes
     * @param $username_or_email
     * @param bool $is_email
     * @return AuthenticationMode
     */
    public function getAuthMode($username_or_email, $is_email=false) {
        $auth_selector = $this->auth_selector;
        $mode = call_user_func($auth_selector, $this->getAuthModes(), $username_or_email, $is_email, $this->getUserClass());
        return $this->authentication_modes[$mode];
    }

    /**
     * Visszaadja a User osztályt
     *
     * @return mixed
     */
    public function getUserClass() {
        return $this->user_class;
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