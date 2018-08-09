<?php

namespace Kodiak\ServiceProvider\TwigProvider\ContentProvider;


class PageTitleProvider extends ContentProvider
{
    public function getValue()
    {
        return $this->getConfiguration()["title"];
    }
}