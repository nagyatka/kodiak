<?php

namespace Kodiak\ServiceProvider\SecurityProvider;


use Kodiak\Security\Model\SecurityManager;
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
    }
}