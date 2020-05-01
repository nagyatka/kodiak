<?php
namespace Kodiak\Request;


use Kodiak\Application;
use Kodiak\Core\KodiConf;

class CronRequest extends Request
{
    /**
     * @var array
     */
    private $cron_configuration;

    /**
     * CronRequest constructor.
     * @param array $cron_configuration
     */
    public function __construct(array $cron_configuration)
    {
        $this->cron_configuration = $cron_configuration;
    }


    public static function get(): Request
    {
        if(!self::$instance){
            $env = Application::get(KodiConf::ENVIRONMENT);
            if(array_key_exists("cron", $env)) {
                self::$instance = new CronRequest($env["cron"]);
            }
            else {
                self::$instance = new CronRequest([]);
            }
        }
        return self::$instance;
    }


    public function getHttpMethod(): string
    {
        return "CRON";
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getUri(): string
    {
        if(array_key_exists("uri", $this->cron_configuration)) {
            return $this->cron_configuration["uri"];
        }

        if(count($_SERVER['argv']) < 2) {
            throw new \Exception("Missing argument");
        }
        return $_SERVER['argv'][1];
    }

}