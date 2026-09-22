<!--
id: readme
tags: ''
-->

# drupal-config-fixer

![Hero](../../images/drupal-config-fixer.jpg)

> Scripted fixes for the nightmare of Drupal config management against localdev.

## Summary

You export config from production, and your local environment needs something different: a spam module that is only enabled in production, a debug module that is only enabled locally, a permission a role gains once that module is on. Every `drush config:export` or pull puts the YAML back the way it was, and the edits have to be made again by hand.

Drupal Config Fixer turns those edits into a short PHP script that you run after every export. It edits the YAML files in your config sync directory directly: it enables and disables modules in `core.extension.yml`, adds and removes permissions and module dependencies in `user.role.*.yml`, and restores or deletes files with git. Each call checks the current state first, so running the script twice gives the same files as running it once.

## Quick Start

Install it (the package is served from GitHub, not Packagist):

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

Save this as `fix.php` next to `config/` and run `php fix.php`:

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

Run it again and the file stays the same.

## Requirements

- PHP 7.3 or newer. The test suite runs on PHP 7.3 through 8.5.
- `git` on your `PATH`, only if you use `files()->restore()`. It runs `git restore`, so the script must run from inside the repository that holds the config.

## Installation

The package is not on Packagist, so first add its GitHub repository:

```shell
composer config repositories.drupal-config-fixer github https://github.com/aklump/drupal-config-fixer
```

Then require the latest stable version:

```shell
composer require aklump/drupal-config-fixer:^0.0
```

Or require the dev channel:

```shell
composer config minimum-stability dev
composer require aklump/drupal-config-fixer:@dev
```

### Web Package

To run the fixes as a [Web Package](https://github.com/aklump/web_package) build hook, run the commands above from within `.web_package/` so the dependency goes into `.web_package/composer.json`, then call the library from a hook file. See the example under Usage.

## Usage

`new ConfigFixer($base_path)` points at your config sync directory. It has three entry points, and every method on them returns the same object, so calls chain.

### Modules: `core.extension.yml`

| Method | Effect |
|---|---|
| `enable($module, $after_module)` | Adds `$module` with weight 0 after `$after_module`. If `$after_module` is not enabled, it is appended at the end. Does nothing if `$module` is already enabled. |
| `disable($module)` | Removes `$module`. |
| `addDependency($module, $after_module = NULL)` | Adds a module to `dependencies.module`, after `$after_module` or at the end. |
| `removeDependency($module)` | Removes a module from `dependencies.module`. |

### Roles: `user.role.<role>.yml`

`$fix->roles(['anonymous', 'authenticated'])` applies each call to every role listed. A role file is rewritten only when its data actually changes.

| Method | Effect |
|---|---|
| `addPermission($permission, $after_permission = NULL)` | Inserts after `$after_permission`, or appends. Skipped if the role already has it. |
| `removePermission($permission)` | Removes the permission. |
| `addDependency($module, $after_module = NULL)` | Inserts into `dependencies.module` after `$after_module`. With no `$after_module`, it goes first. |
| `removeDependency($module)` | Removes the module dependency. |

### Files

| Method | Effect |
|---|---|
| `restore($relative_path)` | Runs `git restore` on the path, so a file the export deleted or changed comes back as committed. Shell wildcards work: `restore('monolog*.*')`. |
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

[BSD-3-Clause](../../../LICENSE)
