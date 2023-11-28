<!--
id: readme
tags: ''
-->

# {{ book.title }}

![Hero](../../images/drupal-config-fixer.jpg)

{{ composer_install|raw }}

## Web Package Installation

Follow the above instructions from within _.web_package/composer.json_

Here is an example Web Package hook file;
```php
use AKlump\Drupal\ConfigFixer\ConfigFixer;

require_once '.web_package/vendor/autoload.php';

$fixer = new ConfigFixer('./private/default/config/base');

$fixer->modules()->disable([
  'environment_indicator',
  'environment_indicator_loft',
]);
$fixer
  ->roles(['anonymous', 'authenticated'])
  ->removeDependency('environment_indicator')
  ->removePermission('access environment indicator');

$fixer->files()->delete(['environment_indicator.settings.yml']);
```
