<?php

declare(strict_types=1);

namespace Hoge\Fuga\Tmp;

use BEAR\Resource\AbstractRequest;
use BEAR\Resource\AbstractUri;
use BEAR\Resource\HalLink;
use BEAR\Resource\ResourceObject;
use Doctrine\Common\Annotations\Reader;
use Nocarrier\Hal;
use ReflectionMethod;

class AnnotationResolver
{
    private Reader $reader;
    private HalLink $link;

    public function __construct(Reader $reader, HalLink $link)
    {
        $this->reader = $reader;
        $this->link = $link;
    }

    public function rebuildBody(ResourceObject $ro): string
    {
        $this->makeHalBody($ro);

        return json_encode($ro->body);
    }

    public function makeHalBody(ResourceObject $ro): void
    {
        [$ro, $body] = $this->valuate($ro);
        $method = 'on' . ucfirst($ro->uri->method);
        $hasMethod = method_exists($ro, $method);
        /** @var list<object> $annotations */
        $annotations = $hasMethod ? $this->reader->getMethodAnnotations(new ReflectionMethod($ro, $method)) : [];
        $hal = $this->getHal($ro->uri, $body, $annotations);
        $json = $hal->asJson(true);
        assert(is_string($json));
        $ro->body = json_decode($json, true);
    }

    private function valuateElements(ResourceObject $ro): void
    {
        assert(is_array($ro->body));
        /** @var mixed $embeded */
        foreach ($ro->body as $key => &$embeded) {
            if (! ($embeded instanceof AbstractRequest)) {
                continue;
            }

            $isNotArray = ! isset($ro->body['_embedded']) || ! is_array($ro->body['_embedded']);
            if ($isNotArray) {
                $ro->body['_embedded'] = [];
            }

            assert(is_array($ro->body['_embedded']));
            // @codeCoverageIgnoreStart
            if ($this->isDifferentSchema($ro, $embeded->resourceObject)) {
                $ro->body['_embedded'][$key] = $embeded()->body;
                unset($ro->body[$key]);

                continue;
            }

            // @codeCoverageIgnoreEnd
            unset($ro->body[$key]);
            $body = $this->rebuildBody($embeded());
            $ro->body['_embedded'][$key] = json_decode($body, null, 512, JSON_THROW_ON_ERROR);
        }
    }

    /**
     * @codeCoverageIgnore
     */
    private function isDifferentSchema(ResourceObject $parentRo, ResourceObject $childRo): bool
    {
        return $parentRo->uri->scheme . $parentRo->uri->host !== $childRo->uri->scheme . $childRo->uri->host;
    }

    /**
     * @param array<array-key, mixed> $body
     * @psalm-param list<object>       $annotations
     * @phpstan-param array<object>    $annotations
     */
    private function getHal(AbstractUri $uri, array $body, array $annotations): Hal
    {
        $query = $uri->query ? '?' . http_build_query($uri->query) : '';
        $path = $uri->path . $query;
        $selfLink = $this->link->getReverseLink($path);
        $hal = new Hal($selfLink, $body);

        return $this->link->addHalLink($body, $annotations, $hal);
    }

    /**
     * @return array{0: ResourceObject, 1: array<array-key, mixed>}
     */
    private function valuate(ResourceObject $ro): array
    {
        if (is_scalar($ro->body)) {
            $ro->body = ['value' => $ro->body];
        }

        if ($ro->body === null) {
            $ro->body = [];
        }

        if (is_object($ro->body)) {
            $ro->body = (array) $ro->body;
        }

        // evaluate all request in body.
        $this->valuateElements($ro);
        assert(is_array($ro->body));

        return [$ro, $ro->body];
    }

    public function hoge(): string
    {
        return __METHOD__;
    }
}
