<?php

namespace Kodiak\ServiceProvider\PandabaseProvider;


use PandaBase\Connection\ConnectionManager;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class PandaBaseProvider implements ServiceProviderInterface
{
    private $configuration;

    /**
     * SessionProvider constructor.
     * @param array $configuration
     */
    public function __construct($configuration)
    {
        $this->configuration = $configuration;
    }

    public function register(Container $pimple)
    {
        $configuration = $this->configuration;
        if($this->isAssociativeConfiguration($configuration)) {
            ConnectionManager::getInstance()->initializeConnection($configuration);
            if(strtolower($configuration["charset"]) === "utf8") {
                if($configuration["driver"] == "mysql") {
                    ConnectionManager::getInstance()->getConnection($configuration["name"])->setNamesUTF8();
                }
            }
        } else {
            ConnectionManager::getInstance()->initializeConnections($configuration);
            foreach ($configuration as $config) {
                if(strtolower($config["charset"]) === "utf8") {
                    if($config["driver"] == "mysql") {
                        ConnectionManager::getInstance()->getConnection($config["name"])->setNamesUTF8();
                    }
                }
            }
        }

        $pimple['db'] = $pimple->factory(function ($c) {
            return ConnectionManager::getInstance();
        });
    }

    private function isAssociativeConfiguration(array $arr)
    {
        if (array() === $arr) return false;
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}