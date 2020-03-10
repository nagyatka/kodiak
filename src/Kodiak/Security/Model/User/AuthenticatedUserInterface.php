<?php

namespace Kodiak\Security\Model\User;


use PandaBase\AccessManagement\AuthorizedUserInterface;

/**
 * Interface UserInterface
 * @package KodiSecurity\Model
 */
interface AuthenticatedUserInterface extends AuthorizedUserInterface
{
    /**
     * @return string
     */
    public function getHashedPassword(): ?string;

    /**
     * Clears every secret from the instance.
     */
    public function clear(): void;

    /**
     * Returns with the username.
     * @return string
     */
    public function getUsername(): ?string;

    /**
     * The username exists in database or not.
     *
     * @return bool
     */
    public function isValidUsername(): bool;

    /**
     * Is the user has active status or not?
     *
     * @return bool
     */
    public function isActive(): bool;

    /**
     * @return int
     */
    public function getUserId(): ?int;

    /**
     * @return array
     */
    public function getRoles(): array;

    /**
     * @param $role
     * @return bool
     */
    public function hasRole($role): bool;

    /**
     * The authentication mode which has to be used for the authentication of the user instance.
     *
     * @return string|null
     */
    public function getAuthModeName(): ?string;

    /**
     * @param int $user_id
     * @return AuthenticatedUserInterface
     */
    public static function getUserByUserId(int $user_id): AuthenticatedUserInterface;

    /**
     * @param string $username
     * @return AuthenticatedUserInterface
     */
    public static function getUserByUsername(string $username): AuthenticatedUserInterface;

    /**
     * @param string $email
     * @return AuthenticatedUserInterface
     */
    public static function getUserByEmail(string $email): AuthenticatedUserInterface;

    /**
     * @param int $user_id
     * @param string $username
     * @param array $roles
     * @return AuthenticatedUserInterface
     */
    public static function getUserFromSecuritySession(?int $user_id, ?string $username, ?array $roles): AuthenticatedUserInterface;
}