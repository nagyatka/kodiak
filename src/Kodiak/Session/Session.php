<?php

namespace Kodiak\Session;

class Session
{
    public function get($name,$defaultValue = null) {
        return $_SESSION[$name] ?? $defaultValue;
    }

    public function set($name,$value,$defaultValue = null) {
        $_SESSION[$name] = ($value != null) ? $value : $defaultValue;
    }
}