# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Fixed

- `Roles::removePermission()` and `Roles::removeDependency()` stopped at the first role with no permissions/dependencies instead of processing the remaining roles.
- `Modules::addDependency()` and `Modules::removeDependency()` failed with "Undefined index" when `core.extension.yml` has no `dependencies` key.
- `Modules::enable()` triggered a PHP 8.3+ deprecation when the "after" module is not enabled; it now appends.
- `Permissions` used an undeclared dynamic property (deprecated in PHP 8.2).
- Restored PHP 7.3 compatibility by removing typed properties.
- `Files::restore()` passed the path to the shell unescaped: a base path containing a space failed silently, and shell metacharacters were executed. It now escapes the path (wildcards are still matched by git) and throws a `RuntimeException` when `git restore` fails.
- Loading an empty YAML file (e.g. an empty `core.extension.yml`) threw a `TypeError`; it now loads as an empty array.
