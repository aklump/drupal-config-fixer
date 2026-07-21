<?php

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
