<?php


namespace Kodiak\Security\Model\Authentication;

use Kodiak\Security\Model\User\AuthenticatedUserInterface;

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
    const USER_SELECTED = "user_selected";

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
            case AuthSelectors::USER_SELECTED:
                return AuthSelectors::class."::userSelected";
            default:
                return $selector_name;
        }
    }

    /**
     *
     * @param array $modes
     * @param string $username_or_email
     * @param bool $is_email
     * @param AuthenticatedUserInterface $user_class
     * @return mixed
     */
    public static function first($modes, $username_or_email, $is_email, $user_class) {
        return $modes[0];
    }

    /**
     * @param array $modes
     * @param string $username_or_email
     * @param bool $is_email
     * @param AuthenticatedUserInterface $user_class
     * @return mixed
     */
    public static function last($modes, $username_or_email, $is_email, $user_class) {
        return $modes[count($modes) - 1];
    }

    /**
     * @param array $modes
     * @param string $username_or_email
     * @param bool $is_email
     * @param AuthenticatedUserInterface $user_class
     * @return mixed
     */
    public static function userSelected($modes, $username_or_email, $is_email, $user_class) {
        $authUser = $is_email ? $user_class::getUserByEmail($username_or_email) : $user_class::getUserByUsername($username_or_email);

        if($authUser->isValidUsername()) {
            return $modes[0];
        }

        $user_selected_mode = $authUser->getAuthModeName();
        if($user_selected_mode == null) {
            return $modes[0];
        }
        if(!array_key_exists($user_selected_mode, $modes)) {
            return $modes[0];
        }
        return $modes[$user_selected_mode];
    }
}