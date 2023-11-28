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

$fix = new ConfigFixer('./private/default/config/base');

$fix->modules()
  ->enable('honeypot', 'help')
  ->enable('captcha', 'breakpoint')
  ->enable('recaptcha', 'rdf')
  ->enable('monolog', 'module_filter')
  ->enable('monolog_conditional_mailer', 'monolog');

$fix->files()
  ->restore('honeypot.settings.yml')
  ->restore('tour.tour.honeypot.yml')
  ->restore('monolog*.*')
  ->restore('captcha.*')
  ->restore('recaptcha*.*');

$fix->roles(['content_editor'])
  ->addDependency('captcha', 'block')
  ->addPermission('skip CAPTCHA', 'set page title');

$fix->roles(['site_admin'])
  ->addDependency('captcha', 'block')
  ->addDependency('captcha')
  ->addDependency('honeypot', 'captcha')
  ->addPermission('administer CAPTCHA settings')
  ->addPermission('administer honeypot', 'administer CAPTCHA settings')
  ->addPermission('bypass honeypot protection', 'administer honeypot')
  ->addPermission('skip CAPTCHA', 'bypass honeypot protection');

$fix->roles(['site_admin'])
  ->addDependency('captcha', 'block')
  ->addDependency('captcha')
  ->addDependency('honeypot', 'captcha')
  ->addPermission('administer CAPTCHA settings')
  ->addPermission('administer honeypot', 'administer CAPTCHA settings')
  ->addPermission('bypass honeypot protection', 'administer honeypot')
  ->addPermission('skip CAPTCHA', 'bypass honeypot protection');

$fix->roles(['anonymous', 'authenticated'])
  ->removeDependency('environment_indicator')
  ->removePermission('access environment indicator');
```
