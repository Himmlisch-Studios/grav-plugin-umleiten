<?php

namespace Grav\Plugin\Umleiten;

use Exception;
use Grav\Common\Grav;
use Grav\Common\Page\Page;
use Grav\Common\Page\Pages;
use Grav\Common\Twig\Twig;
use Illuminate\Support\Str;
use Stringable;

class View implements Stringable
{
    use WithViewMutators;

    readonly string $path;
    readonly protected Twig $twig;
    readonly public string $extension;

    const DEFAULT_EXTENSION = '.html.twig';

    protected function __construct(
        readonly public string $view,
        readonly public ?string $overrideContent = null,
        readonly public bool $dotPath = false,
        readonly public array $data = [],
        ?string $extension = null,
    ) {
        if (!is_null($extension)) {
            $this->extension = $extension;
        }

        $this->twig = Grav::instance()['twig'];

        if ($this->dotPath) {
            $this->path = static::processDotPath($view);

            if (!isset($this->extension)) {
                $this->extension = static::DEFAULT_EXTENSION;
            }
        } else {
            $this->path = $view;

            if (!isset($this->extension)) {
                $this->extension =  '.' . Str::after($view, '.');
            }
        }
    }

    public static function make(string $view, array $data = []): static
    {
        return new static(
            view: $view,
            data: $data,
            dotPath: !str_ends_with($view, '.twig') && !str_contains($view, DS),
        );
    }

    /* ================
     |   GETTTERS
     ================== */

    public function pathWithoutExtension()
    {
        return Str::before($this->path, $this->extension);
    }

    /* ================
     |    HELPERS
     ================== */

    public static function json(array $content): page
    {
        return (new static(
            view: 'default.json.twig',
            data: ['content' => $content],
        ))->asJson();
    }

    public static function route(string $route, ?string $template = null): ?Page
    {
        /** @var Pages */
        $pages = Grav::instance()['pages'];
        $page = $pages->dispatch($route);

        if (!is_null($page) && !is_null($template)) {
            $page->template($template);
        }

        return $page;
    }

    public static function redirect($route): Page
    {
        $page = static::newPage();
        $page->redirect($route);
        $page->init(static::defaultFile());
        return $page;
    }

    /* ================
     |   INTERNAL
     ================== */

    protected static function processDotPath($view)
    {
        $path = str_replace('.', DS, $view);

        return ltrim($path, '/') . static::DEFAULT_EXTENSION;
    }

    protected static function generateSlug(string $string)
    {
        return str_replace(' ', '', preg_replace('/[^A-z0-9]/', '', strtolower($string)));
    }

    protected static function fileFinder(string $file, array $locations)
    {
        $return = '';
        foreach ($locations as $location) {
            if (file_exists($location . '/' . $file)) {
                $return = $location . '/' . $file;
                break;
            }
        }
        return $return;
    }

    protected static function newPage(): Page
    {
        $page = new Page;
        $page->cacheControl(0);
        $page->eTag(0);
        $page->expires(0);

        return $page;
    }

    protected static function defaultFile()
    {
        return new \SplFileInfo(
            Grav::instance()['locator']->findResource(
                static::fileFinder('default.md', ['theme://pages', 'plugins://umleiten/pages']),
                true,
                true
            )
        );
    }

    /* ================
     |    MAGIC
     ================== */

    public function __invoke(): mixed
    {
        if (isset($this->overrideContent)) {
            return $this->overrideContent;
        }

        return $this->twig->processTemplate($this->path, $this->data);
    }

    public function __toString(): string
    {
        return $this();
    }
}
