<?php

namespace AKlump\Drupal\ConfigFixer\Helpers;

use AKlump\Drupal\ConfigFixer\Exception\DrupalFormatMismatchException;
use Symfony\Component\Yaml\Yaml;

/**
 * Check that re-saving a file unchanged would reproduce it byte for byte.
 *
 * The file is expected to be as Drupal exported it. If writing its own data
 * back would change it, this library's YAML format differs from the format of
 * the Drupal that exported it.
 */
class VerifyDrupalFormat {

  /**
   * @param string $path
   *   The file, for the message.
   * @param string $contents
   *   The file's contents as read.
   *
   * @throws \AKlump\Drupal\ConfigFixer\Exception\DrupalFormatMismatchException
   */
  public function __invoke(string $path, string $contents): void {
    $data = Yaml::parse($contents) ?? [];
    $rewritten = (new DumpYaml())($data);
    if ($rewritten === $contents) {
      return;
    }
    $before = explode("\n", $contents);
    $after = explode("\n", $rewritten);
    $line = 0;
    while (($before[$line] ?? NULL) === ($after[$line] ?? NULL)) {
      $line++;
    }
    throw new DrupalFormatMismatchException(sprintf(
      "%s: re-saving this file unchanged would alter line %d, so it is not in the YAML format this library writes. Not writing it.\n  exported: %s\n  would be: %s",
      $path,
      $line + 1,
      $this->describe($before[$line] ?? NULL),
      $this->describe($after[$line] ?? NULL)
    ));
  }

  private function describe(?string $line): string {
    return NULL === $line ? '(end of file)' : var_export($line, TRUE);
  }

}
