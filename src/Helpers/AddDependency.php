<?php

namespace AKlump\Drupal\ConfigFixer\Helpers;

class AddDependency {

  public function __invoke($data, $dependency, $after_item, $type = 'module'): array {
    $data['dependencies'] += [$type => []];
    if (!in_array($dependency, $data['dependencies'][$type])) {
      (new InsertAfterArrayValue())($data['dependencies'][$type], $dependency, $after_item);
    }

    return $data;
  }

}
