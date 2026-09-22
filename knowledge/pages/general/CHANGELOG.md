<!--
id: changelog
tags: ''
-->

# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Fixed

- `Modules::enable()` and `ConfigEntities::addDependency()` threw `DrupalFormatMismatchException` on an empty file; an empty file is now written normally.

## [0.0.13] - 2026-09-22

### Added

- Before rewriting a file, the library checks that the file as exported already matches the YAML format and sort order it writes. If not, it throws `DrupalFormatMismatchException` naming the file and the first difference, and leaves the file untouched, so a Drupal version that writes config differently cannot cause churn.
- `ConfigFixer::config(array $names)` edits any config entity file (a block, a view, a field...): `addDependency()` and `removeDependency()` for module dependencies.

### Changed

- Files are written the way Drupal writes a config export: nothing is inlined, and multi-line strings are literal blocks. Lists a call changes are sorted as Drupal sorts them (permissions and dependency names alphabetically, the module list by weight then name), and a new `dependencies` key goes after `uuid`, `langcode` and `status`. A later `drush config:export` no longer re-sorts what this library wrote.
- The "after" arguments are gone: `Modules::enable($module)`, `Roles::addPermission($permission)` and `Roles::addDependency($module)`. Existing calls keep working, and the position now comes from Drupal's sort order; passing the old argument triggers an `E_USER_WARNING` that names the line to remove it from.
- `Modules::enable()` and `Modules::disable()` no longer rewrite `core.extension.yml` when nothing changes.
- `Files::restore()` runs git in the base path, so it works from any working directory.
- Removing the last dependency of a config entity leaves `dependencies: {  }`, as Drupal writes it.

### Removed

- The `mcaskill/php-array-insert` dependency, no longer used.
- `Modules::addDependency()` and `Modules::removeDependency()`: they wrote a `dependencies` key into `core.extension.yml`, which Drupal does not use. Use `$fix->config([...])->addDependency()` on the config entity that needs the module.
- `Helpers\AddDependency` no longer takes an "after" argument: its signature is now `($data, $dependency, $type = 'module')`.

### Fixed

- `bin/run-phpunit-tests.sh` could create `/test_output` when `INSTALL_PATH` pointed at a missing directory; it now stops with an error.

- `Roles::removePermission()` and `Roles::removeDependency()` stopped at the first role with no permissions/dependencies instead of processing the remaining roles.
- `Modules::addDependency()` and `Modules::removeDependency()` failed with "Undefined index" when `core.extension.yml` has no `dependencies` key.
- `Modules::enable()` triggered a PHP 8.3+ deprecation when the "after" module is not enabled; it now appends.
- `Permissions` used an undeclared dynamic property (deprecated in PHP 8.2).
- Restored PHP 7.3 compatibility by removing typed properties.
- `Files::restore()` passed the path to the shell unescaped: a base path containing a space failed silently, and shell metacharacters were executed. It now escapes the path (wildcards are still matched by git) and throws a `RuntimeException` when `git restore` fails.
- Loading an empty YAML file (e.g. an empty `core.extension.yml`) threw a `TypeError`; it now loads as an empty array.
