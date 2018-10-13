<?php
namespace Kodiak\Request;


class CronRequest extends Request
{
    public static function get(): Request
    {
        if(!self::$instance){
            self::$instance = new CronRequest();
        }
        return self::$instance;
    }


    public function getHttpMethod(): string
    {
        return "CRON";
    }

    public function getUri(): string
    {
        if(count($_SERVER['argv']) < 2) {
            throw new \Exception("Missing argument");
        }
        return $_SERVER['argv'][1];
    }

}