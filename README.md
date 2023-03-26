# Bladestan

Static analysis for Blade templates in Laravel projects.

## Install

```bash
composer require tomasvotruba/bladestan --dev
```

## Configure

Configure paths to your Blade views, unless you use the default `resources/views` directory:

```yaml
parameters:
    bladestan:
        template_paths:
            # default
            - resources/views
```

## Features

### Custom Error Formatter

We provide custom PHPStan error formatter to better display the template errors:

* clickable template file path link to the error in blade template
* clickable controller file path to source `view()` call

```bash
 ------ -----------------------------------------------------------
  Line   app/Http/Controllers/PostCodexController.php
 ------ -----------------------------------------------------------
  20     Call to an undefined method App\Entity\Post::getConten().
         rendered in: post_codex.blade.php:15
 ------ -----------------------------------------------------------
```

How to use custom error formatter?

```bash
vendor/bin/phpstan analyze --error-format blade
```

## Credits

- [Can Vural](https://github.com/canvural) - this package is based on that, with upgrade for Laravel 10 and active maintenance
- [All Contributors](https://github.com/TomasVotruba/bladestan/graphs/contributors)
