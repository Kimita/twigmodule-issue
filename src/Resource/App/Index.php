<?php

declare(strict_types=1);

namespace Hoge\Fuga\Resource\App;

use BEAR\Resource\Annotation\Embed;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\ResourceObject;

class Index extends ResourceObject
{
    #[Embed(rel: 'weekday', src: '/weekday?year=2022&month=11&day=8')]
    #[Link(rel: 'weekday', href: 'app://self/weekday?year=2022&month=11&day=8')]
    public function onGet(): static
    {
        $this->body = $this->body ?? [];
        $this->body += ['hello' => 'world'];

        return $this;
    }
}
