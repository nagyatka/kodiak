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
    public function login(array $credentials): AuthenticationTaskResult
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
            return new AuthenticationTaskResult(false, null);
        }

        // Cseck password expiry
        if (!$userCandidate["password_expire"] || $userCandidate["password_expire"]<date('Y-m-d H:i:s')) {
            return new AuthenticationTaskResult(false, 'PASSWORD_EXPIRED');
        }

        unset($userCandidate["password"]);

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
        ConnectionManager::getInstance()->persist($user);

        return new AuthenticationTaskResult(true, null);
    }

    public function changePassword(array $credentials): AuthenticationTaskResult
    {

        /** @var AuthenticatedUserInterface $userClassName */
        $userClassName = $this->getConfiguration()["user_class_name"];


        // Meglévő jelszó ellenőrzése
        $checkPassword = $this->login(["username"=>$credentials["username"], "password" => $credentials["old_password"]]);
        if (!$checkPassword->isSuccess()) {
            return new AuthenticationTaskResult(false, "INVALID_PASSWORD");
        }


        // Új jelszók egyformák
        if($credentials["password"] !== $credentials["repassword"]) {
            $authResult = new AuthenticationTaskResult(false, "MISMATCHED_PASSWORDS");
            return $authResult;
        }

        // Jelszó policy
        // 1. Jelszó history (utolsó 6 jelszó)
        // 2. Jelszó erősség
        
        $username = $credentials["username"];
        $user = $userClassName::getUserByUsername($username);
        $user["password"] = $this->hashPassword($credentials["password"])->output;
        ConnectionManager::getInstance()->persist($user);

        return new AuthenticationTaskResult(true, null);
    }
}
