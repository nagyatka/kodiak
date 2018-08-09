<?php

namespace Kodiak\Session\Handler;


use Kodiak\Application;
use PandaBase\Connection\ConnectionManager;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler;

class PandabaseSessionHandler extends PdoSessionHandler
{
    public function __construct(array $configuration)
    {
        /** @var ConnectionManager $db */
        $db = Application::get("db");
        $connection = $db->getDefault();
        if($configuration["connection_name"] != "default") {
            $connection = $db->getConnection($configuration["connection_name"]);
        }
        parent::__construct($connection->getDatabase(), $configuration["options"]);
    }

}