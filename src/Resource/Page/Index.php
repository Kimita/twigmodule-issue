<?php

declare(strict_types=1);

namespace Hoge\Fuga\Resource\Page;

use BEAR\Resource\Annotation\Embed;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\ResourceObject;

class Index extends ResourceObject
{
    /** @var array{greeting: string} */
    public $body;

    /** @return static */
    #[Embed(rel: 'weekday', src: 'app://self/weekday?year=2022&month=11&day=8')]
    #[Link(rel: 'weekday', href: 'app://self/weekday?year=2022&month=11&day=8')]
    public function onGet(string $name = 'BEAR.Sunday'): static
    {
        $this->body = $this->body ?? [];
        $this->body += [
            'greeting' => 'Hello ' . $name,
        ];

        return $this;
    }
}
