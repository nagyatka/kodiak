<?php

namespace Kodiak\ServiceProvider\SecurityProvider;


use Kodiak\Security\Model\SecurityManager;
use Kodiak\ServiceProvider\TwigProvider\Twig;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class SecurityProvider implements ServiceProviderInterface
{
    private $configuration;

    /**
     * SecurityProvider constructor.
     * @param $configuration
     */
    public function __construct($configuration)
    {
        $this->configuration = $configuration;
    }


    public function register(Container $pimple)
    {
        $conf = $this->configuration;
        $pimple['security'] = $pimple->factory(function ($c) use($conf) {
            return new SecurityManager($conf);
        });

        $pimple->extend('twig', function ($twig, $c) {
            /** @var Twig $mytwig */
            $mytwig = $twig;
            /** @var SecurityManager $securityManager */
            $securityManager = $c["security"];
            $get_user = new \Twig_SimpleFunction("get_user",function() use($securityManager) {
                return $securityManager->getUser();
            });
            $mytwig->getTwigEnvironment()->addFunction($get_user);
            return $mytwig;
        });
    }
}