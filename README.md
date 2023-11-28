# Drupal Config Fixer

![Hero](images/drupal-config-fixer.jpg)

## Install with Composer

1. Because this is an unpublished package, you must define it's repository in your project's _composer.json_ file. Add the following to _composer.json_:

    ```json
    "repositories": [
        {
            "type": "github",
            "url": "https://github.com/aklump/drupal-config-fixer"
        }
    ]
    ```

1. Then `composer require aklump/drupal-config-fixer:^0.0`    

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
