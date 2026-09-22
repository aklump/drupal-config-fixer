<?php

namespace AKlump\DrupalConfigFixer\Tests\TestTraits;

/**
 * Run code that may trigger E_USER_WARNING without PHPUnit turning it into an
 * exception, so a test can check both the warning and what the code did.
 */
trait CaptureWarningsTrait {

  /**
   * @return string[]
   *   The messages of the E_USER_WARNINGs $callback triggered.
   */
  protected function captureWarnings(callable $callback): array {
    $warnings = [];
    set_error_handler(function (int $severity, string $message) use (&$warnings) {
      $warnings[] = $message;

      return TRUE;
    }, E_USER_WARNING);
    try {
      $callback();
    }
    finally {
      restore_error_handler();
    }

    return $warnings;
  }

}
