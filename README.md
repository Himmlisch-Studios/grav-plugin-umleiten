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


Create routes listening for the `onRegisterRoutes` event.

```php
public static function getSubscribedEvents()
{
    return [
        'onRegisterRoutes' => ['onRegisterRoutes', 0],
    ];
}

public function onRegisterRoutes()
{
    // Executed after onPagesInitialized. 
    // You have access to Router::instance() and $this->grav['router'].
}
```

We create routes in a similar fashion of many MVC Frameworks. But this particular way is based on [Laravel](https://github.com/laravel/framework).

```php
use Grav\Plugin\Umleiten\Route;
use Grav\Plugin\Umleiten\View;
use Grav\Theme\Controllers\AuthController;
use Grav\Theme\Controllers\TeamController;
use Grav\Theme\Middlewares\AuthMiddleware;

public function onRegisterRoutes()
{
    Route::get('/app', function () {
        return View::make('app');
    })->middleware(AuthMiddleware::class);

    Route::get('/app/logout', [AuthController::class, 'logout'])
        ->middleware(AuthMiddleware::class);

    Route::get('/app/signin', function () {
        return View::make('signin');
    });
    Route::post('/app/signin', [AuthController::class, 'login']);

    Route::get('/app/signup', function () {
        return View::make('signup');
    });
    Route::post('/app/signup', [AuthController::class, 'register']);

    Route::get('/app/teams/create', [TeamController::class, 'create']);
    Route::post('/app/teams/create', [TeamController::class, 'store']);
}
```

We declare routes using `Grav\Plugin\Umleiten\Route` and passing a controller. The controllar can be a function array, a Closure or an invokable class.

We can also declare Middlewares to modify the request or intercept it.

```php
<?php

namespace Grav\Theme\Middlewares;

use Closure;
use Grav\Common\Grav;
use Nyholm\Psr7\ServerRequest;
use Grav\Common\Page\Page;
use Grav\Plugin\Umleiten\Middleware;
use Grav\Plugin\Umleiten\View;

class AuthMiddleware extends Middleware
{
    function __invoke(ServerRequest $request, Closure $next): Mixed
    {
        /** @var Session */
        $session = Grav::instance()['session'];
        $value = $session->__get('some_custom_value');

        if (is_null($value)) {
            // Return anything diferent to `ServerRequest` to intercept the process
            return View::redirect('/app/signin');
        }

        return $next($request); // Continue to the next Middleware
    }
}
```

At the end of `onRegisterRoutes`, the `Router` will try to find a match for the current `$request`.

If a `Route` is matched by the `$request`, it will be **booted** and will execute its Middlewares in order, passing the `$request` for possible mutations.

If the `$request` is still a `ServerRequest` at the end of the Middlewares execution, it will be passed in to the Controller. Otherwise it will return a `$response`.

At the end of the `Router` process, the `$response` will try to be solved depending its type:

- `Grav\Common\Page\Page`: Will force Grav to process the page on that route.
- `Psr\Http\Message\ServerRequestInterface`: Will set `$this->grav['request']` as it.
- Anything else will tried to be output directly as string.

If the `$response` was null or gave an error on output, the Route will be ignored, and the Grav process will continue.

---

As you could see, the plugin comes with a static class `View` with a bunch of helpers to make the code more abstract.

We can create a View by passing a twig template and data directly to it.

```php
$template = 'some_registered_template.html.twig';
View::make($template, [
    'myData' => [0,1,2,3]
]);
```

`View` implements `Stringable`, so if we return a `View` directly through a `Route`, it will be processed and output as HTML string.

However, we may want to still take advantage of Frontmatter and the Grav Page Processor.

For this, we want to return a `Page` using one of the following methods:

- `withPage($pagePath)`: Processes the template with the path of a file given (Normally an `.md`)
- `asPage()`: Processes the template with the file corresponding to the same path as the template
- `asJson()`: Processes the template and then the output is passed to an empty JSON page.
- `withJsonPage($pagePath)`: Same as `withPage` but tries to force the `template` and `language` to JSON.
- `asJsonPage()`: Same as `asPage` but tries to force the `template` and `language` to JSON.

**Example:**

`theme/pages/signin.md`:

```
---
title: Sign In
cache_enable: false

form:
  name: login

  fields:
    email:
      label: Email
      placeholder: Enter your email address
      type: email
      validate:
        required: true
    password:
      label: Password
      type: password
      validate:
        required: true
  buttons:
    submit:
      type: submit
      value: Submit
    reset:
      type: reset
      value: Reset
---

# Sign In
```

`theme/templates/signin.html.twig`:

```twig
{% extends 'partials/base.html.twig' %}


{% block body %}
    <div class="signin-form">
        {{ page.content|raw }}
        {% include "forms/form.html.twig" %}
    </div>
{% endblock %}
```

Then inside `onRegisterRoutes`:

```php
Route::get('/app/signin', function () {
    return View::make('signin')->asPage();
});
```

## Need a website?

We at [Himmlisch Web](https://web.himmlisch.com.mx), want you to help connect your brand and your customers, by creating a perfectly tailored website that improves productivity and speaks for your business and values. [Contact us!](https://himmlisch.com.mx/contact).