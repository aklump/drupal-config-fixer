<?php

namespace AKlump\Drupal\ConfigFixer\Helpers;

/**
 * Warn a caller that still passes the removed "after" argument.
 *
 * enable(), addPermission() and addDependency() once took the item to insert
 * after. The position now follows Drupal's sort order, so the argument is
 * ignored; PHP accepts the extra argument silently, hence the warning.
 */
class WarnIgnoredAfterArgument {

  /**
   * @param string $method
   *   The method that received the argument, e.g. __METHOD__.
   */
  public function __invoke(string $method): void {
    // PHP reports a warning where trigger_error() runs, i.e. here, so name the
    // line in the caller's script that needs editing.
    $call = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? [];
    $where = isset($call['file'], $call['line']) ? sprintf(' (called in %s on line %d)', $call['file'], $call['line']) : '';
    trigger_error(sprintf('%s(): the second ("after") argument is ignored; items are placed in Drupal\'s sort order. Remove the argument%s.', $method, $where), E_USER_WARNING);
  }

}
