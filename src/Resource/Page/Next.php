<?php

declare(strict_types=1);

namespace Hoge\Fuga\Resource\Page;

use BEAR\Resource\Annotation\Embed;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\ResourceObject;

class Next extends ResourceObject
{
    #[Embed(rel: 'weekday', src: 'app://self/weekday{?year,month,day}')]
    #[Link(rel: 'index', href: '/index')]
    public function onGet(int $year, int $month, int $day): static
    {
        $params = ['year' => $year, 'month' => $month, 'day' => $day];
        $this->body = $this->body ?? [];
        $this->body += $params;

        return $this;
    }
}
