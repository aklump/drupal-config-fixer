<?php

namespace AKlump\Drupal\ConfigFixer\Exception;

/**
 * A config file is not written the way this library writes Drupal config.
 *
 * The library copies Drupal's YAML format and sort rules. Before it rewrites
 * a file, it checks that the file as Drupal exported it already matches those
 * rules. When it does not, this site's Drupal writes config differently from
 * the copied rules, and rewriting the file would add unrelated changes.
 */
class DrupalFormatMismatchException extends \RuntimeException {

}
