<?php

namespace AKlump\Drupal\ConfigFixer\Helpers;

use AKlump\Drupal\ConfigFixer\Exception\DrupalFormatMismatchException;

/**
 * Check that a list in an exported file is already in Drupal's order.
 *
 * Called before the library re-sorts the list. If Drupal did not leave the
 * list in the order the library would, the copied sort rule is not the one
 * this site's Drupal uses, and re-sorting would reorder unrelated entries.
 */
class VerifyOrder {

  /**
   * @param string $label
   *   What the list is, for the message, e.g. "permissions".
   * @param string[] $actual
   *   The entries as exported.
   * @param string[] $expected
   *   The same entries in the order the library would write them.
   *
   * @throws \AKlump\Drupal\ConfigFixer\Exception\DrupalFormatMismatchException
   */
  public function __invoke(string $label, array $actual, array $expected): void {
    if ($actual === $expected) {
      return;
    }
    throw new DrupalFormatMismatchException(sprintf(
      "%s are not in the order this library sorts them. Not writing the file.\n  exported: %s\n  would be: %s",
      $label,
      implode(', ', $actual),
      implode(', ', $expected)
    ));
  }

}
