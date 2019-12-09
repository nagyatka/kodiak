<?php


namespace Kodiak\Security\Model\Authentication;


use Kodiak\Security\Model\Authentication\AuthenticationInterface;

abstract class AuthenticationMode
{
    protected $parameters;

    /**
     * AuthenticationMode constructor.
     * @param $parameters
     */
    public function __construct($parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * Authentikációs mód neve.
     *
     * @return string
     */
    public abstract static function name();

    /**
     * Használandó user osztály neve namespace-szel ellátva.
     *
     * @return string
     */
    public abstract function userClass();

    /**
     * Az authentikációt leíró osztály neve namespace-szel ellátva.
     *
     * @return AuthenticationInterface
     */
    public abstract function getAuthenticationInterface();

    /**
     * Authentikációs útvonalak, amik a modulhoz tartoznak.
     *
     * @return array
     */
    public abstract function routes();

    /**
     * Authentikációs módhoz szükséges adatbázis táblák
     *
     * @return array
     */
    public abstract function tables();

    /**
     * Jogosultságok, amik a routes-ban megadott útvonalakhoz kellenek.
     * 
     * @return mixed
     */
    public abstract function permissions();

}