# Umleiten Plugin

The **Umleiten** Plugin is an extension for [Grav CMS](http://github.com/getgrav/grav).

**Umleiten** let's you create custom routes for your pages and plugins. 

Made by [Himmlisch Web](https://web.himmlisch.com.mx). Licensed under the [MIT License](LICENSE).

## Installation

Installing the Umleiten plugin can be done in one of three ways: The GPM (Grav Package Manager) installation method lets you quickly install the plugin with a simple terminal command, the manual method lets you do so via a zip file, and the admin method lets you do so via the Admin Plugin.

### GPM Installation (Preferred)

To install the plugin via the [GPM](http://learn.getgrav.org/advanced/grav-gpm), through your system's terminal (also called the command line), navigate to the root of your Grav-installation, and enter:

    bin/gpm install umleiten

This will install the Umleiten plugin into your `/user/plugins`-directory within Grav. Its files can be found under `/your/site/grav/user/plugins/umleiten`.

### Manual Installation

To install the plugin manually, download the zip-version of this repository and unzip it under `/your/site/grav/user/plugins`. Then rename the folder to `umleiten`. You can find these files on [GitHub](https://github.com//grav-plugin-umleiten) or via [GetGrav.org](http://getgrav.org/downloads/plugins#extras).

You should now have all the plugin files under

    /your/site/grav/user/plugins/umleiten
	
> NOTE: This plugin is a modular component for Grav which may require other plugins to operate, please see its [blueprints.yaml-file on GitHub](https://github.com//grav-plugin-umleiten/blob/master/blueprints.yaml).

### Admin Plugin

If you use the Admin Plugin, you can install the plugin directly by browsing the `Plugins`-menu and clicking on the `Add` button.

## Configuration

Before configuring this plugin, you should copy the `user/plugins/umleiten/umleiten.yaml` to `user/config/plugins/umleiten.yaml` and only edit that copy.

Here is the default configuration and an explanation of available options:

```yaml
enabled: true
```

Note that if you use the Admin Plugin, a file with your configuration named umleiten.yaml will be saved in the `user/config/plugins/`-folder once the configuration is saved in the Admin.

## Usage

Firstly, you must hook the `onPagesInitialized` event and call `Router::boot()`.

```php
public static function getSubscribedEvents()
{
    return [
        'onPagesInitialized' => ['onPagesInitialized', 0],
    ];
}

public function onPagesInitialized()
{
    \Grav\Plugin\Umleiten\Router::boot();
}
```

Now, you must create a `routes.php` file in the same folder path as the script where you're calling the boot.

Once done, you can start creating routes, inside a returning array, in a similar fashion of many MVC Frameworks (although this particular way is based on [Laravel](https://github.com/laravel/framework)).

```php
<?php // user/themes/mytheme/routes.php

use Grav\Plugin\Umleiten\Route;
use Grav\Plugin\Umleiten\View;
use Grav\Theme\Controllers\AuthController;
use Grav\Theme\Controllers\TeamController;
use Grav\Theme\Middlewares\AuthMiddleware;

return [
    Route::get('/app', function () {
        return View::make('app');
    })->middleware(AuthMiddleware::class),

    Route::get('/app/logout', [AuthController::class, 'logout'])
        ->middleware(AuthMiddleware::class),

    Route::get('/app/signin', function () {
        return View::make('signin');
    }),
    Route::post('/app/signin', [AuthController::class, 'login']),

    Route::get('/app/signup', function () {
        return View::make('signup');
    }),
    Route::post('/app/signup', [AuthController::class, 'login']),

    Route::get('/app/teams/create', [TeamController::class, 'create']),
    Route::post('/app/teams/create', [TeamController::class, 'store']),
];
```

As you could see, the plugin comes with some static class helpers such as `Grav\Plugin\Umleiten\View` to make the code more abstract.

Example of middleware:

```php
<?php

namespace Grav\Theme\Middlewares;

use Grav\Common\Grav;
use Nyholm\Psr7\ServerRequest;
use Grav\Common\Page\Page;
use Grav\Plugin\Umleiten\Middleware;
use Grav\Plugin\Umleiten\View;

class AuthMiddleware extends Middleware
{
    function __invoke(ServerRequest $request): ?Page
    {
        /** @var Session */
        $session = Grav::instance()['session'];
        $value = $session->__get('some_custom_value');

        if (is_null($value)) {
            return View::redirect('/app/signin'); // Return a view to intercept the process
        }

        return null; // Return null to continue the process
    }
}
```

## Need a website?

We at [Himmlisch Web](https://web.himmlisch.com.mx), want you to help connect your brand and your customers, by creating a perfectly tailored website that improves productivity and speaks for your business and values. [Contact us!](https://himmlisch.com.mx/contact).