<?php

namespace Kodiak\Core\Module;


class ModuleParams
{
    /**
     * @var ModuleParams
     */
    private static $instance;

    /**
     * @var array
     */
    private $params;

    /**
     * ModuleParams constructor.
     */
    private function __construct()
    {
        $this->params = [];
    }

    /**
     * @return ModuleParams
     */
    public static function getInstance(): ModuleParams
    {
        if(!self::$instance) self::$instance = new ModuleParams();
        return self::$instance;
    }

    /**
     * @param array $params
     */
    public static function setParams(array $params): void {
        self::getInstance()->params = $params;
    }

    /**
     * @param string $key
     * @return mixed|null
     */
    public static function get(string $key): ?mixed {
        return self::getInstance()->params[$key];
    }

}