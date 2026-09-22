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
