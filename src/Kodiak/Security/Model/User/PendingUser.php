<?php

namespace Kodiak\Security\Model\User;


class PendingUser implements AuthenticatedUserInterface
{

    private $user_id;

    /**
     * PendingUser constructor.
     * @param $user_id
     */
    public function __construct($user_id)
    {
        $this->user_id = $user_id;
    }


    /**
     * @return string
     */
    public function getHashedPassword(): ?string
    {
        return null;
    }

    /**
     * Clears every secret from the instance.
     */
    public function clear(): void
    {

    }

    /**
     * Returns with the username.
     * @return string
     */
    public function getUsername(): ?string
    {
        return null;
    }

    /**
     * The username exists in database or not.
     *
     * @return bool
     */
    public function isValidUsername(): bool
    {
       return false;
    }

    /**
     * @return int
     */
    public function getUserId(): ?int
    {
        return $this->user_id;
    }

    /**
     * @return array
     */
    public function getRoles(): array
    {
        return [Role::PENDING_USER];
    }

    /**
     * @param $role
     * @return bool
     */
    public function hasRole($role): bool
    {
        return $role == Role::PENDING_USER;
    }

    /**
     * @param int $user_id
     * @return AuthenticatedUserInterface
     */
    public static function getUserByUserId(int $user_id): AuthenticatedUserInterface
    {
        return new PendingUser($user_id);
    }

    /**
     * @param string $username
     * @return AuthenticatedUserInterface
     */
    public static function getUserByUsername(string $username): AuthenticatedUserInterface
    {
        return new PendingUser(-1);
    }

    /**
     * @param string $email
     * @return AuthenticatedUserInterface
     */
    public static function getUserByEmail(string $email): AuthenticatedUserInterface
    {
        return new PendingUser(-1);
    }

    /**
     * @param int $user_id
     * @param string $username
     * @param array $roles
     * @return AuthenticatedUserInterface
     */
    public static function getUserFromSecuritySession(?int $user_id, ?string $username, ?array $roles): AuthenticatedUserInterface
    {
        return new PendingUser(-1);
    }

    /**
     * The user has root privileges or not?
     *
     * @return bool
     */
    public function isRoot()
    {
        return false;
    }

    /**
     * Returns with array of access group ids of the user.
     *
     * @return array
     */
    public function getAccessGroups()
    {
        return $this->getRoles();
    }

    /**
     * Returns with the 2FA secret
     * @return mixed
     */
    public function get2FASecret()
    {
        return null;
    }
}