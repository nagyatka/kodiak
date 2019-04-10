<?php

namespace Kodiak\ServiceProvider\MonologProvider;


use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 * Class MonologProvider
 *
 * Supported handlers: StreamHandler
 *
 * @package KodiMonologProvider\MonologProvider
 */
class MonologProvider implements ServiceProviderInterface
{
    /**
     * @var array
     */
    private $configuration;

    /**
     * MonologProvider constructor.
     * @param array $configuration
     */
    public function __construct(array $configuration)
    {
        $this->configuration = $configuration;
    }


    public function register(Container $pimple)
    {

        $conf = $this->configuration;
        $pimple["logger"] = $pimple->factory(function($c) use ($conf){

            $loggerStore = [];
            foreach ($conf as $conf_item) {
                $logger = new Logger($conf_item["name"]);
                foreach ($conf_item["handlers"] as $handler) {
                    switch ($handler["class_name"]) {
                        case StreamHandler::class:
                            $logger->pushHandler(new StreamHandler($handler["file_path"],$handler["log_level"]));
                    }
                }
                $loggerStore[$conf_item["name"]] = $logger;
            }


            if(count($loggerStore) < 1) {
                return null;
            }
            elseif (count($loggerStore) == 1) {
                return array_values($loggerStore)[0];
            }
            else {
                return $loggerStore;
            }
        });
    }
}