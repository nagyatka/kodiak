<?php

namespace Kodiak\Security\Model\Authentication;


class AuthenticationRequest
{

    const REQ_LOGIN         = 1;
    const REQ_REGISTER      = 2;
    const REQ_DEREGISTER    = 3;
    const REQ_RESET_PASS    = 4;
    const REQ_CHANGE_PASS   = 5;
    const REQ_LOGOUT        = 6;


    /**
     * @var int
     */
    private $type;

    /**
     * @var array
     */
    private $credentials;

    /**
     * AuthenticationRequest constructor.
     * @param int $type
     * @param array $credentials
     */
    public function __construct($type, array $credentials)
    {
        $this->type = $type;
        $this->credentials = $credentials;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @return array
     */
    public function getCredentials(): array
    {
        return $this->credentials;
    }
}