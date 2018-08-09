<?php

namespace Kodiak\Security\Model\User;


class Role
{
    const ANON_USER = 0;
    const PENDING_USER = 1; // For two-factor auth
    const AUTH_USER = 2;
    const SUP_USER = 9;
    const ADMIN = 99;

    private static $roles = [
        "anon"      => self::ANON_USER,
        "pending"   => self::PENDING_USER,
        "auth_user" => self::AUTH_USER,
        "sup_user"  => self::SUP_USER,
        "admin"     => self::ADMIN,
    ];

    /**
     * @param string $key
     * @return int|null
     */
    public static function get(string $key): ?int {
        if(!array_key_exists($key,self::$roles)) return null;
        return self::$roles[$key];
    }
}