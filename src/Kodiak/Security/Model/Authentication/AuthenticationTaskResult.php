<?php

namespace Kodiak\Security\Model\Authentication;

class AuthenticationTaskResult
{
    /**
     * @var bool
     */
    private $success;

    /**
     * @var mixed
     */
    private $result;

    /**
     * AuthenticationTaskResult constructor.
     * @param bool $success
     * @param mixed $result
     */
    public function __construct($success, $result)
    {
        $this->success = $success;
        $this->result = $result;
    }


    /**
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param mixed $result
     */
    public function setResult($result)
    {
        $this->result = $result;
    }
}