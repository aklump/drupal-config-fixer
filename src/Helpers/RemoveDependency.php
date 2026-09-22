<?php

namespace AKlump\Drupal\ConfigFixer\Helpers;

/**
 * Remove a dependency from a config entity's data.
 */
class RemoveDependency {

  /**
   * @param array $data
   *   The config entity's data.
   * @param string $dependency
   *   The name of the dependency, e.g. a module name.
   * @param string $type
   *   The type of dependency: config, content, module or theme.
   *
   * @return array
   *   $data without the dependency. A type left with no names is removed, as
   *   Drupal does; "dependencies" itself stays, possibly empty.
   */
  public function __invoke(array $data, string $dependency, string $type = 'module'): array {
    $names = $data['dependencies'][$type] ?? [];
    $key = array_search($dependency, $names);
    if (FALSE === $key) {
      return $data;
    }
    unset($names[$key]);
    if ($names) {
      $data['dependencies'][$type] = array_values($names);
    }
    else {
      unset($data['dependencies'][$type]);
    }

    return $data;
  }

}
