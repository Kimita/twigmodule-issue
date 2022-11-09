<?php

declare(strict_types=1);

namespace Hoge\Fuga\Module;

use BEAR\Resource\RenderInterface;
use Hoge\Fuga\Tmp\AnnotationResolver;
use Hoge\Fuga\Tmp\TwigRenderer;
use Madapaja\TwigModule\TwigErrorPageModule;
use Madapaja\TwigModule\TwigModule;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;

class HtmlModule extends AbstractModule
{

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->install(new TwigModule());
        $this->install(new TwigErrorPageModule());
        $this->override(new class () extends AbstractModule{
            protected function configure(): void
            {
                $this->bind(AnnotationResolver::class);
                $this->bind(RenderInterface::class)
                    ->to(TwigRenderer::class)
                    ->in(Scope::SINGLETON);
            }
        });
    }
}
