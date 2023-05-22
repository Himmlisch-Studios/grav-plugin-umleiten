<?php

namespace Grav\Plugin\Umleiten;

use Grav\Common\Grav;
use Grav\Common\Page\Page;

trait WithViewMutators
{
    public function withPage(string  $pagePath): Page
    {
        $pageFullPath = Grav::instance()['locator']->findResource(
            $pagePath,
            true,
            true
        );

        if (is_null($pageFullPath) || empty($pageFullPath)) {
            return static::error();
        }

        /** @var Page */
        $page = self::newPage();

        $page->template($this->pathWithoutExtension());
        $page->init(
            new \SplFileInfo($pageFullPath)
        );

        $page->slug(self::generateSlug($page->title()));
        $page->route($this->view);
        $page->templateFormat(ltrim($page->extension(), '.'));

        return $page;
    }

    public function asPage(
        string $extension = '.md',
        array $lookupPaths = ['theme://pages']
    ): Page {

        $path = $this->pathWithoutExtension() . $extension;

        $pagePath = static::fileFinder(
            $path,
            $lookupPaths
        );

        $page = $this->withPage($pagePath);;

        return $page;
    }

    public function withJsonPage(string $pagePath): Page
    {
        $page = $this->withPage($pagePath);

        $page->templateFormat('json');
        $page->language('json');

        return $page;
    }

    public function asJsonPage(
        string $extension = '.json'
    ): Page {
        $page = $this->asPage($extension);
        $page->templateFormat('json');
        $page->language('json');

        return $page;
    }

    public function asJson(): Page
    {
        $content = (string) $this;

        /** @var Page */
        $page = self::newPage();
        $page->template($this->pathWithoutExtension());
        $page->templateFormat('json');
        $page->language('json');
        $page->setRawContent(json_decode($content));

        $page->init(self::defaultFile());

        return $page;
    }
}
