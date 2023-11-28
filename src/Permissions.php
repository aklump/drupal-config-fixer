<?php

namespace AKlump\Drupal\ConfigFixer;

use AKlump\Drupal\ConfigFixer\Helpers\InsertAfterArrayValue;

class Permissions {

  /**
   * @param string $module
   * @param $after_module
   *
   * @return $this
   */
  public function remove(string $module): self {
    if (empty($this->data['permissions'])) {
      return $this;
    }
    $key = array_search($module, $this->data['permissions']);
    if (FALSE !== $key) {
      unset($this->data['permissions'][$key]);
      $this->data['permissions'] = array_values($this->data['permissions']);
    }

    return $this;
  }

  /**
   * @param string $permission
   * @param $after_permission
   *
   * @return $this
   */
  public function add(string $permission, $after_permission = NULL): self {
    $this->data['permissions'] = $this->data['permissions'] ?? [];
    if (!in_array($permission, $this->data['permissions'])) {
      (new InsertAfterArrayValue())($this->data['permissions'], $permission, $after_permission);
    }

    return $this;
  }

}
