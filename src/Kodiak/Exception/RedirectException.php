<?php

namespace Kodiak\Exception;


use Throwable;

class RedirectException extends \Exception
{
    private $redirectUrl;

    public function __construct($redirectUrl, $message = "", $code = 0, Throwable $previous = null)
    {
        $this->redirectUrl = $redirectUrl;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string
     */
    public function getRedirectUrl(): string
    {
        return $this->redirectUrl;
    }

}