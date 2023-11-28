<?php

namespace AKlump\Drupal\ConfigFixer\Helpers;

class InsertAfterArrayValue {

  public function __invoke(array &$array, $value, $after_value) {
    if ($after_value) {
      $key = array_search($after_value, $array);
      if (FALSE !== $key) {
        $handled = TRUE;
        array_splice($array, $key + 1, 0, [$value]);
      }
    }
    if (empty($handled)) {
      $array[] = $value;
    }
  }

}
