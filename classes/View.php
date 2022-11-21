<?php

namespace Grav\Plugin\Umleiten;

use Grav\Common\Grav;
use Grav\Common\Page\Page;
use Grav\Common\Page\Pages;

class View
{
    public static function make(string $view, ?string $template = null, ?string $page_path = null): ?Page
    {
        /** @var \RocketTheme\Toolbox\ResourceLocator */
        $locator = Grav::instance()['locator'];

        $path = str_replace('.', DS, $view);
        $page_path = $page_path ? str_replace('.', DS, $page_path) : $path;
        $trailPath = '/' . trim($path, '/');
        $template = str_replace('.', DS, $template);

        $view_file = static::fileFinder(
            $page_path . '.md',
            ['theme://pages']
        );

        if (is_null($view_file) || empty($view_file)) {
            return static::error();
        }

        $view_file = $locator->findResource(
            $view_file,
            true,
            true
        );

        $page = new Page();

        /** @var Pages */
        $pages = Grav::instance()['pages'];
        $parent = $pages->find(str_replace($trailPath, '', $path));

        if ($parent == null) {
            $parent = $pages->find('/');
            $parent->template('default');
        }

        $page->parent($parent);

        if (!empty($template)) {
            $page->template($template);
        }

        $page->init(
            new \SplFileInfo($view_file)
        );

        $page->route($path);
        $page->slug(self::generateSlug($page->title()));
        return $page;
    }

    public static function page(string $route, ?string $template = null): ?Page
    {
        /** @var Pages */
        $pages = Grav::instance()['pages'];
        $page = $pages->dispatch($route);

        if (!is_null($page) && !is_null($template)) {
            $page->template($template);
        }

        return $page;
    }

    public static function error()
    {
        $page = new Page();
        $page->init(
            new \SplFileInfo(
                Grav::instance()['locator']->findResource(
                    static::fileFinder('error.md', ['plugins://error/pages']),
                    true,
                    true
                )
            )
        );
        return $page;
    }

    public static function redirect($route): Page
    {
        $page = new Page();
        $page->redirect($route);
        $page->init(
            new \SplFileInfo(
                Grav::instance()['locator']->findResource(
                    static::fileFinder('default.md', ['theme://pages']),
                    true,
                    true
                )
            )
        );
        return $page;
    }

    private static function generateSlug(string $string)
    {
        return str_replace(' ', '', preg_replace('/[^A-z0-9]/', '', strtolower($string)));
    }

    private static function fileFinder(string $file, array $locations)
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
}
