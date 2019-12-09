<?php


namespace Kodiak\Security\Model\Authentication;

/**
 * Class AuthSelectors
 *
 * Kellenek:
 *  - milyen módok vannak ($modes)
 *  - felhasználónév ($username)
 *
 *
 * Selector metódust mindig az AuthSelectors::get($name) metódussal kell elkérni. Ha az esetleg nem lenne beregisztrálva
 * akkor a $name paraméterben érkező értéket fogja visszadni.
 *
 * @package Ilx\Module\Security\Model\Auth
 */
class AuthSelectors
{
    const FIRST = "first";
    const LAST = "last";

    /**
     * @param $selector_name
     * @return string
     */
    public static function get($selector_name) {
        switch ($selector_name) {
            case AuthSelectors::FIRST:
                return AuthSelectors::class."::first";
            case AuthSelectors::LAST:
                return AuthSelectors::class."::last";
            default:
                return $selector_name;
        }
    }

    public static function first($modes, $username) {
        return $modes[0];
    }

    public static function last($modes, $username) {
        return $modes[count($modes) - 1];
    }
}