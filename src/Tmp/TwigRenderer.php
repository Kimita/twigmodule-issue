<?php

/**
 * This file is part of the Madapaja.TwigModule package.
 *
 * @license http://opensource.org/licenses/MIT MIT
 */

namespace Hoge\Fuga\Tmp;

use BEAR\Resource\Code;
use BEAR\Resource\RenderInterface;
use BEAR\Resource\ResourceObject;
use Madapaja\TwigModule\Annotation\TwigRedirectPath;
use Madapaja\TwigModule\Exception\TemplateNotFound;
use Madapaja\TwigModule\TemplateFinder;
use Madapaja\TwigModule\TemplateFinderInterface;
use Ray\Aop\WeavedInterface;
use Ray\Di\Di\Inject;
use ReflectionClass;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Loader\FilesystemLoader;
use Twig\TemplateWrapper;

class TwigRenderer implements RenderInterface
{
    /**
     * File extension
     *
     * @var string
     */
    const EXT = '.html.twig';

    /**
     * @var \Twig\Environment
     */
    public $twig;

    /**
     * @var string
     */
    private $redirectPage;

    /**
     * @var TemplateFinderInterface
     */
    private $templateFinder;

    /**
     * @var null|AnnotationResolver
     */
    private $annotationResolver = null;

    /**
     * @TwigRedirectPath("redirectPage")
     */
    #[TwigRedirectPath('redirectPage')]
    public function __construct(
        Environment $twig,
        string $redirectPage,
        TemplateFinderInterface $templateFinder = null
    ) {
        $this->twig = $twig;
        $this->redirectPage = $redirectPage;
        $this->templateFinder = $templateFinder ?: new TemplateFinder();
    }

    #[Inject]
    public function setAnnotationResolver(AnnotationResolver $resolver): void
    {
        $this->annotationResolver = $resolver;
    }

    /**
     * {@inheritdoc}
     */
    public function render(ResourceObject $ro): string
    {
        $this->annotationResolver?->rebuildBody($ro);

        $this->setContentType($ro);

        if ($this->isNoContent($ro)) {
            $ro->view = '';

            return $ro->view;
        }
        if ($this->isRedirect($ro)) {
            $ro->view = $this->renderRedirectView($ro);

            return $ro->view;
        }
        $ro->view = $this->renderView($ro);

        return $ro->view;
    }

    private function setContentType(ResourceObject $ro): void
    {
        if (! isset($ro->headers['Content-Type'])) {
            $ro->headers['Content-Type'] = 'text/html; charset=utf-8';
        }
    }

    private function renderView(ResourceObject $ro): string
    {
        return $this->load($ro)->render($this->buildBody($ro));
    }

    private function renderRedirectView(ResourceObject $ro): string
    {
        try {
            return $this->twig->render($this->redirectPage, ['url' => $ro->headers['Location']]);
        } catch (LoaderError $e) {
            return '';
        }
    }

    private function load(ResourceObject $ro): TemplateWrapper
    {
        try {
            return $this->loadTemplate($ro);
        } catch (LoaderError $e) {
            if ($ro->code === 200) {
                throw new TemplateNotFound($e->getMessage(), 500, $e);
            }
            return throw $e;
        }
    }

    private function isNoContent(ResourceObject $ro): bool
    {
        return $ro->code === Code::NO_CONTENT || $ro->view === '';
    }

    private function isRedirect(ResourceObject $ro): bool
    {
        return \in_array($ro->code, [
            Code::MOVED_PERMANENTLY,
            Code::FOUND,
            Code::SEE_OTHER,
            Code::TEMPORARY_REDIRECT,
            Code::PERMANENT_REDIRECT,
        ], true) && isset($ro->headers['Location']);
    }

    private function loadTemplate(ResourceObject $ro): TemplateWrapper
    {
        $reflection = $this->getReflection($ro)
            ?: throw new TemplateNotFound('Reflection Error: ' . get_class($ro));

        $loader = $this->twig->getLoader();
        if ($loader instanceof FilesystemLoader) {
            $classFile = $reflection->getFileName();
            $templateFile = ($this->templateFinder)($classFile);

            return $this->twig->load($templateFile);
        }

        return $this->twig->load($reflection->name . self::EXT);
    }

    /**
     * @return ReflectionClass<ResourceObject>|ReflectionClass<object>|false
     */
    private function getReflection(ResourceObject $ro): false|ReflectionClass
    {
        if ($ro instanceof WeavedInterface) {
            return (new ReflectionClass($ro))->getParentClass();
        }

        return new ReflectionClass($ro);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildBody(ResourceObject $ro): array
    {
        $body = \is_array($ro->body) ? $ro->body : [];
        $body += ['_ro' => $ro];

        return $body;
    }
}
