<?php

declare(strict_types=1);

namespace Hoge\Fuga\Module;

use Madapaja\TwigModule\TwigErrorPageModule;
use Madapaja\TwigModule\TwigModule;
use Ray\Di\AbstractModule;

class HtmlModule extends AbstractModule
{

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->install(new TwigModule());
        $this->install(new TwigErrorPageModule());
    }
}
