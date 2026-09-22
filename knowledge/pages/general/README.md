<!--
id: readme
tags: ''
-->

# drupal-config-fixer

> A files-only YAML patcher for the nightmare of Drupal config management against localdev.

![Hero](../../images/drupal-config-fixer.jpg)

## Summary

**This is not a Drupal module, and it is not integrated with Drupal.** It never bootstraps Drupal, touches the database or calls Drush. It is a layer over your config export: the YAML files in your config sync directory, and nothing else. It works on any Drupal site that exports its configuration as YAML, which is Drupal 8 and later.

Developing locally, you change things that should never ship: a debug module you enabled, a permission you granted to test something, a config file that disappeared along the way. The next `drush config:export` writes those local adjustments into the YAML beside the work you want to keep.

Modules that manage this from inside Drupal, such as Config Split, depend on Drupal's own config system. Drupal Config Fixer only edits files. You write the corrections once as a short PHP script and run it after each export to take the local adjustments back out, so the config you commit is ready for production.

Every change is to what the config says, never to the site. `enable()` and `disable()` add a module to, or take it off, the module list in `core.extension.yml`; Drupal installs or uninstalls the module only when it imports that file. The same holds for the permissions and module dependencies it adds to and removes from `user.role.*.yml`, and for the config files it deletes outright or restores from git.

Each call checks the file first, so running the script twice gives the same files as running it once. It also writes each file in the format and order Drupal uses, so the next export does not re-sort what it changed.

## Quick Start

From your project root (the directory with your `composer.json`; in an empty directory, run `composer init -n` first), install it. It is served from GitHub, not Packagist:

```shell
composer config repositories.drupal-config-fixer github https://github.com/aklump/drupal-config-fixer
composer require aklump/drupal-config-fixer:^0.0
```

Say you turned on Devel locally and gave content editors a Devel permission to test something, and `drush config:export` wrote both into your config. `config/user.role.content_editor.yml` now contains:

```yaml
uuid: 6d7c8a4e-0000-4000-8000-000000000001
langcode: en
status: true
dependencies:
  module:
    - devel
id: content_editor
label: 'Content editor'
weight: 2
is_admin: false
permissions:
  - 'access content overview'
  - 'access devel information'
  - 'set page title'
```

Save this as `fix.php` in your project root and run `php fix.php`:

```php
<?php
require __DIR__ . '/vendor/autoload.php';

use AKlump\Drupal\ConfigFixer\ConfigFixer;

$fix = new ConfigFixer('./config');
$fix->modules()->disable('devel');
$fix->roles(['content_editor'])
  ->removePermission('access devel information')
  ->removeDependency('devel');
```

Devel is gone from the module list in `core.extension.yml`, and the role file now reads:

```yaml
uuid: 6d7c8a4e-0000-4000-8000-000000000001
langcode: en
status: true
dependencies: {  }
id: content_editor
label: 'Content editor'
weight: 2
is_admin: false
permissions:
  - 'access content overview'
  - 'set page title'
```

Run it again and nothing changes. Drupal is not involved at any point: the script only rewrote the files, ready to commit.

## Requirements

- A Drupal 8 or later site whose configuration is exported as YAML. The library reads only those files, so no Drupal version is loaded or checked.
- PHP 7.3 or newer. The test suite runs on PHP 7.3 through 8.5.
- `git` on your `PATH`, only if you use `files()->restore()`.

## Installation

Run these from your project root, the directory that holds your `composer.json`; in an empty directory, run `composer init -n` first. The package is not on Packagist, so first add its GitHub repository:

```shell
composer config repositories.drupal-config-fixer github https://github.com/aklump/drupal-config-fixer
```

Then require the latest stable version:

```shell
composer require aklump/drupal-config-fixer:^0.0
```

Or, instead of the stable version, require the dev channel. The `@dev` flag applies to this package only, so the rest of your project stays on stable releases:

```shell
composer require aklump/drupal-config-fixer:@dev
```

### Web Package

To run the fixes as a [Web Package](https://github.com/aklump/web_package) build hook, run the commands above from within `.web_package/` so the dependency goes into `.web_package/composer.json` (run `composer init` there first if it has none), then call the library from a hook file. See the example under Usage.

## Usage

`new ConfigFixer($base_path)` points at your config sync directory. A relative `$base_path` resolves from the current working directory, and every path you pass to a method is relative to `$base_path`. It has four entry points, and every method on them returns the same object, so calls chain. Each call reads the file, changes it and writes it back straight away, and only when something actually changed. Calls are not transactional: when one throws, files already written by earlier calls, or for earlier roles in the same call, stay written.

Older scripts may pass a second, "after" argument to `enable()`, `addPermission()` or `addDependency()`. The library ignores it, since the position now comes from Drupal's sort order, and triggers an `E_USER_WARNING` naming the method and the line of your script to fix. The call still runs.

### Written the way Drupal writes it

Every file is saved with the same YAML settings Drupal uses for a config export, and every list a call changes is put in the order Drupal would give it: permissions and dependency names alphabetically, the module list by weight and then name, and a new `dependencies` key after `uuid`, `langcode` and `status`. So a later `drush config:export` does not re-sort what this library wrote, and you choose no positions yourself. These rules are copied from Drupal core, not loaded from it, so each file is checked before it is rewritten. The file on disk is taken to be exactly what your Drupal exported. If re-saving it unchanged would alter any byte, or a list the call is about to re-sort is not already in that order, your Drupal writes config differently from the copied rules. The call then throws a `DrupalFormatMismatchException` naming the file and the first difference, and leaves that file untouched:

```text
user.role.editor.yml: permissions are not in the order this library sorts them. Not writing the file.
  exported: set page title, access content
  would be: access content, set page title
```

A file the call does not need to change is never checked. A file you edited by hand, for example to add a comment, fails the check the same way; export it again from Drupal first.

A file a call needs but cannot find throws a Symfony `ParseException`; an empty file is treated as having no data, and is not format-checked, so a call can write to it.

### Modules: `core.extension.yml`

The method names follow Drupal's terms, but they only change the file. A module is installed when Drupal imports a `core.extension.yml` that lists it, not when you call `enable()`, and a module's own config files are never touched.

| Method | Effect |
|---|---|
| `enable($module)` | Adds `$module` to the module list with weight 0, sorted into place. A module already listed keeps its weight. |
| `disable($module)` | Takes `$module` off the module list. Its config files stay; remove them with `files()->delete()` if you need to. |

### Roles: `user.role.<role>.yml`

`$fix->roles(['anonymous', 'authenticated'])` applies each call to every role listed.

| Method | Effect |
|---|---|
| `addPermission($permission)` | Adds the permission, sorted into place. Skipped if the role already has it. |
| `removePermission($permission)` | Removes the permission. |
| `addDependency($module)` | Adds the module to `dependencies.module`, sorted into place. |
| `removeDependency($module)` | Removes the module dependency. When it was the last one, `dependencies` is left as `{  }`, as Drupal writes it. |

### Any config entity: `config()`

`$fix->config(['block.block.captcha', 'views.view.content'])` edits any config entity files, named without or with `.yml`. Use it to add or remove the module dependency of a block, a view, a field or anything else that records one. Only module dependencies can be added or removed; `config`, `content`, `theme` and `enforced` dependencies already in a file are kept as they are.

| Method | Effect |
|---|---|
| `addDependency($module)` | Adds the module to `dependencies.module`, sorted into place. |
| `removeDependency($module)` | Removes the module dependency. When it was the last one, `dependencies` is left as `{  }`, as Drupal writes it. |

### Files

| Method | Effect |
|---|---|
| `restore($relative_path)` | Runs `git restore` on the path, so a file the export deleted or changed comes back as committed. Git runs in `$base_path`, so it works from any working directory. Wildcards work, matched by git as a pathspec: `restore('monolog*.*')`. Throws a `RuntimeException` if git fails, for example when the path matches no file git knows. |
| `delete($relative_path)` | Deletes exactly that file if it exists, and does nothing if it does not. No wildcards. |

### A complete hook

This is the kind of script the package was written for (from `knowledge/examples/web_package_hook.php`):

```php
<?php

use AKlump\Drupal\ConfigFixer\ConfigFixer;

$fix = new ConfigFixer('./private/default/config/base');

$fix->modules()
  ->enable('honeypot')
  ->enable('captcha')
  ->enable('monolog')
  ->enable('monolog_conditional_mailer');

$fix->files()
  ->restore('honeypot.settings.yml')
  ->restore('monolog*.*')
  ->restore('captcha.*');

$fix->roles(['site_admin'])
  ->addDependency('captcha')
  ->addDependency('honeypot')
  ->addPermission('administer CAPTCHA settings')
  ->addPermission('administer honeypot')
  ->addPermission('skip CAPTCHA');

$fix->roles(['anonymous', 'authenticated'])
  ->removeDependency('environment_indicator')
  ->removePermission('access environment indicator');
```

## License

[BSD-3-Clause](../../../LICENSE)
