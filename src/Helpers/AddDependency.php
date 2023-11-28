<?php

namespace AKlump\Drupal\ConfigFixer\Helpers;

class AddDependency {

  /**
   * @param $data
   * @param $dependency
   * @param $after_item
   *   If this is empty, $dependency will be added to the beginning.  If you
   *   want it at the end you should indicate the key of the last dependency.
   * @param $type
   *   The type of dependency.  Currently only "module".
   *
   * @return array
   */
  public function __invoke($data, $dependency, $after_item, $type = 'module'): array {
    $data['dependencies'] = $data['dependencies'] ?? [];
    $data['dependencies'] += [$type => []];
    if (!in_array($dependency, $data['dependencies'][$type])) {
      if (empty($after_item)) {
        $data['dependencies'][$type] = array_merge([$dependency], $data['dependencies'][$type]);
      }
      else {
        (new InsertAfterArrayValue())($data['dependencies'][$type], $dependency, $after_item);
      }
    }

    return $data;
  }

}
