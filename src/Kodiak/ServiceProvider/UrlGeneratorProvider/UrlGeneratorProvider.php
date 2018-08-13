<?php

namespace Kodiak\ServiceProvider\UrlGeneratorProvider;

use Kodiak\ServiceProvider\TwigProvider\Twig;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class UrlGeneratorProvider implements ServiceProviderInterface
{

    public function __construct()
    {
    }


    /**
     * Registers services on the given container.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     *
     * @param Container $pimple A container instance
     */
    public function register(Container $pimple)
    {
        $pimple['url_generator'] = function ($c) {
            return new UrlGenerator();
        };

        //url_generate függvény hozzáadása a twighez
        $pimple->extend('twig', function ($twig, $c) {
            /** @var Twig $mytwig */
            $mytwig = $twig;
            /** @var UrlGenerator $urlGenerator */
            $urlGenerator = $c["url_generator"];
            $url_generator = new \Twig_SimpleFunction("url_generate",function($url_name,$parameters = []) use($urlGenerator) {
                return $urlGenerator->generate($url_name,$parameters);
            });
            $mytwig->getTwigEnvironment()->addFunction($url_generator);
            return $mytwig;
        });
    }
}