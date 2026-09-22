# drupal-config-fixer

> A files-only YAML patcher for the nightmare of Drupal config management against localdev.

![Hero](images/drupal-config-fixer.jpg)

## Summary

**This is not a Drupal module, and it is not integrated with Drupal.** It never bootstraps Drupal, touches the database or calls Drush. It is a layer over your config export: the YAML files in your config sync directory, and nothing else. It works on any Drupal site that exports its configuration as YAML, which is Drupal 8 and later.

Developing locally, you change things that should never ship: a debug module you enabled, a permission you granted to test something, a config file that disappeared along the way. The next `drush config:export` writes those local adjustments into the YAML beside the work you want to keep.

Modules that manage this from inside Drupal, such as Config Split, depend on Drupal's own config system. Drupal Config Fixer only edits files. You write the corrections once as a short PHP script and run it after each export to take the local adjustments back out, so the config you commit is ready for production.

Every change is to what the config says, never to the site. `enable()` and `disable()` add a module to, or take it off, the module list in `core.extension.yml`; Drupal installs or uninstalls the module only when it imports that file. The same holds for the permissions and module dependencies it adds to and removes from `user.role.*.yml`, and for the config files it deletes or restores from git.

Each call checks the file first, so running the script twice gives the same files as running it once.

## Quick Start

From your project root (the directory with your `composer.json`), install it. It is served from GitHub, not Packagist:

```shell
composer config repositories.drupal-config-fixer github https://github.com/aklump/drupal-config-fixer
composer require aklump/drupal-config-fixer:^0.0
```

Say `config/user.role.content_editor.yml` contains:

```yaml
id: content_editor
label: 'Content editor'
permissions:
  - 'access content overview'
  - 'set page title'
```

Save this as `fix.php` in your project root and run `php fix.php`. The second argument of `addPermission()` is the permission to insert after; leave it out to append.

```php
<?php
require __DIR__ . '/vendor/autoload.php';

use AKlump\Drupal\ConfigFixer\ConfigFixer;

$fix = new ConfigFixer('./config');
$fix->roles(['content_editor'])
  ->addDependency('captcha')
  ->addPermission('skip CAPTCHA', 'access content overview');
```

The role file now reads:

```yaml
id: content_editor
label: 'Content editor'
permissions:
  - 'access content overview'
  - 'skip CAPTCHA'
  - 'set page title'
dependencies:
  module:
    - captcha
```

Run it again and the file stays the same. Drupal is not involved at any point: the script only rewrote the file, ready to commit.

## Requirements

- A Drupal 8 or later site whose configuration is exported as YAML. The library reads only those files, so no Drupal version is loaded or checked.
- PHP 7.3 or newer. The test suite runs on PHP 7.3 through 8.5.
- `git` on your `PATH`, only if you use `files()->restore()`. It runs `git restore`, so the script must run from inside the repository that holds the config.

## Installation

Run these from your project root, the directory that holds your `composer.json`. The package is not on Packagist, so first add its GitHub repository:

```shell
composer config repositories.drupal-config-fixer github https://github.com/aklump/drupal-config-fixer
```

Then require the latest stable version:

```shell
composer require aklump/drupal-config-fixer:^0.0
```

Or require the dev channel. The `@dev` flag applies to this package only, so the rest of your project stays on stable releases:

```shell
composer require aklump/drupal-config-fixer:@dev
```

### Web Package

To run the fixes as a [Web Package](https://github.com/aklump/web_package) build hook, run the commands above from within `.web_package/` so the dependency goes into `.web_package/composer.json`, then call the library from a hook file. See the example under Usage.

## Usage

`new ConfigFixer($base_path)` points at your config sync directory. A relative `$base_path` resolves from the current working directory, and every path you pass to a method is relative to `$base_path`. It has three entry points, and every method on them returns the same object, so calls chain. Each call reads the file, changes it and writes it back straight away.

### Modules: `core.extension.yml`

The method names follow Drupal's terms, but they only change the file. A module is installed when Drupal imports a `core.extension.yml` that lists it, not when you call `enable()`, and a module's own config files are never touched.

| Method | Effect |
|---|---|
| `enable($module, $after_module)` | Adds `$module` to the module list, with weight 0, right after `$after_module`. If `$after_module` is not in the list, it is appended at the end. Does nothing if `$module` is already there. The list is not re-sorted the way Drupal sorts it. |
| `disable($module)` | Takes `$module` off the module list. Its config files stay; remove them with `files()->delete()` if you need to. |
| `addDependency($module, $after_module = NULL)` | Adds a module to `dependencies.module`, after `$after_module` or, with none, at the end. |
| `removeDependency($module)` | Removes a module from `dependencies.module`. |

### Roles: `user.role.<role>.yml`

`$fix->roles(['anonymous', 'authenticated'])` applies each call to every role listed. A role file is rewritten only when its data actually changes.

| Method | Effect |
|---|---|
| `addPermission($permission, $after_permission = NULL)` | Inserts after `$after_permission`, or appends when it is missing or not given. Skipped if the role already has it. |
| `removePermission($permission)` | Removes the permission. |
| `addDependency($module, $after_module = NULL)` | Inserts into `dependencies.module` after `$after_module`. With no `$after_module` it goes **first**, unlike `modules()->addDependency()`, which appends. |
| `removeDependency($module)` | Removes the module dependency. |

### Files

| Method | Effect |
|---|---|
| `restore($relative_path)` | Runs `git restore` on the path, so a file the export deleted or changed comes back as committed. Wildcards work, matched by git as a pathspec: `restore('monolog*.*')`. Throws a `RuntimeException` if git fails, for example when the path matches no file git knows. |
| `delete($relative_path)` | Deletes the file if it exists. |

### A complete hook

This is the kind of script the package was written for (from `knowledge/examples/web_package_hook.php`):

```php
<?php

use AKlump\Drupal\ConfigFixer\ConfigFixer;

$fix = new ConfigFixer('./private/default/config/base');

$fix->modules()
  ->enable('honeypot', 'help')
  ->enable('captcha', 'breakpoint')
  ->enable('monolog', 'module_filter')
  ->enable('monolog_conditional_mailer', 'monolog');

$fix->files()
  ->restore('honeypot.settings.yml')
  ->restore('monolog*.*')
  ->restore('captcha.*');

$fix->roles(['site_admin'])
  ->addDependency('captcha', 'block')
  ->addDependency('honeypot', 'captcha')
  ->addPermission('administer CAPTCHA settings')
  ->addPermission('administer honeypot', 'administer CAPTCHA settings')
  ->addPermission('skip CAPTCHA', 'administer honeypot');

$fix->roles(['anonymous', 'authenticated'])
  ->removeDependency('environment_indicator')
  ->removePermission('access environment indicator');
```

## License

[BSD-3-Clause](LICENSE)
