<?php

namespace Kodiak\ServiceProvider\TwigProvider\ContentProvider;

abstract class ContentProvider
{
    /**
     * @var array
     */
    private $configuration;

    /**
     * @var string
     */
    private $key;

    /**
     * ContentProvider constructor.
     *
     *
     * @param array $configuration
     */
    final public function __construct(array $configuration)
    {
        $this->key = $configuration["name"];
        unset($configuration["name"]);
        $this->configuration = $configuration;
    }


    public function setValue($name, $value) {
        $this->configuration[$name] = $value;
    }


    /**
     * @return array
     */
    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @return mixed
     */
    abstract public function getValue();
}