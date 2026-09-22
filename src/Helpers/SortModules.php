<?php

namespace AKlump\Drupal\ConfigFixer\Helpers;

/**
 * Sort a core.extension.yml module list by weight, then name.
 *
 * A port of \Drupal\Core\Extension\ModuleWeight::sort() (formerly
 * module_config_sort()), which Drupal applies whenever it saves the list.
 */
class SortModules {

  /**
   * @param array $modules
   *   Module weights keyed by module name.
   *
   * @return array
   *   The same weights, sorted.
   */
  public function __invoke(array $modules): array {
    $sort = [];
    foreach ($modules as $name => $weight) {
      // Prefix negative weights with 0, positive weights with 1, and pad to
      // 19 digits, so a plain string sort orders by weight and then by name.
      $prefix = (int) ($weight >= 0);
      $sort[] = $prefix . sprintf('%019d', abs($weight)) . $name;
    }
    array_multisort($sort, SORT_STRING, $modules);

    return $modules;
  }

}
