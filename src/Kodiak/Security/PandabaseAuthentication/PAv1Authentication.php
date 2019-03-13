<?php

namespace Kodiak\Security\PandabaseAuthentication;


use Kodiak\Security\Model\Authentication\AuthenticationInterface;
use Kodiak\Security\Model\Authentication\AuthenticationTaskResult;
use Kodiak\Security\Model\User\AuthenticatedUserInterface;
use PandaBase\Connection\ConnectionManager;

/**
 * Class PAv1Authentication
 *
 * It is a simple Pandabase ORM based authentication implementation. It uses username/password pair to authenticate a user.
 *
 * Mandatory fields:
 *
 * Login    -> "username", "password"
 * Register -> "username", "email", "firstname", "lastname", "password", "repassword"
 *
 *
 *
 * @package KodiSecurity\Model\Authentication
 */
class PAv1Authentication extends AuthenticationInterface
{
    /**
     * @param array $credentials
     * @return AuthenticationTaskResult
     */
    public function login(array $credentials, bool $allowExpiry = false): AuthenticationTaskResult
    {
        /** @var AuthenticatedUserInterface $userClassName */
        $userClassName = $this->getConfiguration()["user_class_name"];

        $username = $credentials["username"];
        $passwordCandidate = $credentials["password"];
        $userCandidate = $userClassName::getUserByUsername($username);

        // If the username doesnt exist, we stop the auth process with error.
        if(!$userCandidate->isValidUsername()) {
            return new AuthenticationTaskResult(false, null);
        }

        // Check password
        if(!$this->checkPbkdf2($userCandidate,$passwordCandidate)) {
            $userCandidate->incrementFaildPasswordCount();
            return new AuthenticationTaskResult(false, 'PASSWORD_ERROR');
        }

        // Check lockout
        if ($userCandidate->isLockedOut()) {
            return new AuthenticationTaskResult(false, 'USER_LOCKED');            
        }

        // Check password expiry
        if (!$allowExpiry) {
            if (!$userCandidate["password_expire"] || $userCandidate["password_expire"]<date('Y-m-d H:i:s')) {
                return new AuthenticationTaskResult(false, 'PASSWORD_EXPIRED');
            }
        }

        $userCandidate->unLock(); // reset the faild login count to 0     

        unset($userCandidate["password"]);
        unset($userCandidate["mfa_secret"]);

        return new AuthenticationTaskResult(true, $userCandidate);
    }

    public function register(array $credentials): AuthenticationTaskResult
    {
        /** @var AuthenticatedUserInterface $userClassName */
        $userClassName = $this->getConfiguration()["user_class_name"];

        // Check mandatory fields existence
        $fields = ["username", "email", "firstname", "lastname", "password", "repassword"];
        foreach ($fields as $field) {
            if(!isset($credentials[$field])) {
                $authResult = new AuthenticationTaskResult(false, "MISSING_FIELD");
                return $authResult;
            }
        }

        if(($userClassName::getUserByUsername($credentials["username"]))->isValidUsername()) {
            $authResult = new AuthenticationTaskResult(false, "USERNAME_EXISTS");
            return $authResult;
        }

        if(($userClassName::getUserByEmail($credentials["email"]))->isValidUsername()) {
            $authResult = new AuthenticationTaskResult(false, "EMAIL_EXISTS");
            return $authResult;
        }

        if($credentials["password"] !== $credentials["repassword"]) {
            $authResult = new AuthenticationTaskResult(false, "MISMATCHED_PASSWORDS");
            return $authResult;
        }
        $credentials["password"] = $this->hashPassword($credentials["password"])->output;

        $user = new $userClassName([
            "username"  => $credentials["username"],
            "email"     => $credentials["email"],
            "firstname" => $credentials["firstname"],
            "lastname"  => $credentials["lastname"],
            "password"  => $credentials["password"],
        ]);
        ConnectionManager::getInstance()->persist($user);

        return new AuthenticationTaskResult(true, $user);
    }

    public function deRegister(array $credentials): AuthenticationTaskResult
    {
        // TODO: Implement deRegister() method.
        return new AuthenticationTaskResult(false, null);
    }

    public function resetPassword(array $credentials): AuthenticationTaskResult
    {

        /** @var AuthenticatedUserInterface $userClassName */
        $userClassName = $this->getConfiguration()["user_class_name"];
        
        // Új jelszók egyformák
        if($credentials["password"] !== $credentials["repassword"]) {
            return new AuthenticationTaskResult(false, "MISMATCHED_PASSWORDS");
        }

        $resetToken = $credentials["token"];
        $user = $userClassName::getUserByToken($resetToken);

        // Token ellenőrzés
        if ($user["reset_token"]!==$resetToken) {
            return new AuthenticationTaskResult(false, "MISMATCHED_TOKENS");
        }

        // Jelszó policy
        // 1. Jelszó history (utolsó 6 jelszó)
        // 2. Jelszó erősség
        

        $user["password"] = $this->hashPassword($credentials["password"])->output;
        $user["reset_token"] = null;
        $user["failed_login_count"] = 0;
        ConnectionManager::getInstance()->persist($user);

        return new AuthenticationTaskResult(true, null);
    }

    public function changePassword(array $credentials): AuthenticationTaskResult
    {

        /** @var AuthenticatedUserInterface $userClassName */
        $userClassName = $this->getConfiguration()["user_class_name"];

        $username = $credentials["username"];
        $userCandidate = $userClassName::getUserByUsername($username);


        // If the username doesnt exist, we stop the auth process with error.
        if(!$userCandidate->isValidUsername()) {
            return new AuthenticationTaskResult(false, null);
        }

        // Check password
        if(!$this->checkPbkdf2($userCandidate,$credentials["old_password"])) {
            return new AuthenticationTaskResult(false, 'PASSWORD_ERROR');
        }

        // Check lockout
        if ($userCandidate->isLockedOut()) {
            return new AuthenticationTaskResult(false, 'USER_LOCKED');            
        }

        // Új jelszók egyformák
        if($credentials["password"] !== $credentials["repassword"]) {
            $authResult = new AuthenticationTaskResult(false, "MISMATCHED_PASSWORDS");
            return $authResult;
        }

        // New != Old
        if($credentials["old_password"] == $credentials["password"]) {
            return new AuthenticationTaskResult(false, "PASSWORD_IN_HISTORY");
        }

        if (!$this->checkPasswordComplexity($credentials["password"])) {
            return new AuthenticationTaskResult(false, 'PASSWORD_COMPLEXITY_FAIL');
        }

        $username = $credentials["username"];
        $user = $userClassName::getUserByUsername($username);
        $user["password"] = $this->hashPassword($credentials["password"])->output;

        if (!$user->checkPasswordHistory($user["password"])) {
            return new AuthenticationTaskResult(false, 'PASSWORD_IN_HISTORY');
        }

        ConnectionManager::getInstance()->persist($user);
        $user->addPasswordToHistory($user["password"]);
        return new AuthenticationTaskResult(true, null);
    }

    private function checkPasswordComplexity($pwd) {
        $pw_ok = true;

        if (strlen($pwd) < 10) {
            $pw_ok = false;
        }

        if (!preg_match("#[0-9]+#", $pwd)) {
            $pw_ok = false;
        }

        if (!preg_match("#[a-z]+#", $pwd)) {
            $pw_ok = false;
        }     

        if (!preg_match("#[A-Z]+#", $pwd)) {
            $pw_ok = false;
        }     

        return $pw_ok;        
    }
}
