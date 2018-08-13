<?php

namespace Kodiak\ServiceProvider\UrlGeneratorProvider;

use Exception;
use Kodiak\Application;

class UrlGenerator
{

    /**
     * UrlGenerator constructor.
     */
    public function __construct()
    {
    }

    public function generate($url_name,$parameters = []) {
        $routes = Application::getInstance()->getCore()->getRouter()->getRoutes();

        if(!isset($routes[$url_name])) {
            return $url_name;
        }

        $url = $routes[$url_name]["url"];
        $resultUrl = "";
        $index = 0;
        while(($firstPos = strpos($url,"{",$index)) != false) {
            $lastPos = $this->getPositionOfPairCurlyBrackets($url,$firstPos);
            $name = explode(":",substr($url,$firstPos+1,($lastPos-$firstPos-1)))[0];
            $resultUrl .= substr($url,$index,$firstPos-$index).(isset($parameters[$name])?$parameters[$name]:"");
            $index = $lastPos+1;
        };
        if($index != strlen($url)) {
            $resultUrl.= substr($url,$index);
        }
        // Ha nincs semmi, akkor nem volt paraméter, másoljuk az egész url-t.
        if($resultUrl === "") {
            $resultUrl = $url;
        }
        return $resultUrl;

    }

    private function getPositionOfPairCurlyBrackets($str,$start) {

        $openingCount = 1;

        $index = $start + 1;
        while($openingCount != 0) {
            // Megnézzük, hogy hol van a kövi záró
            $nextIndex = strpos($str,"}",$index);
            if($index == false) {
                throw new Exception("Parse error in route (missing } ): ".$str);
            } else {
                // Egy nyitott zárójelhez biztosan találtunk párt.
                $openingCount--;
            }
            // Megnézzük, hogy közben mennyi nyitó volt
            $openingCount += substr_count(substr($str,$index,$nextIndex-$index),"{");

            //Növeljük az indexet
            $index = $nextIndex+1;

        }
        return $index-1;
    }
}