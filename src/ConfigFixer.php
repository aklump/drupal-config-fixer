<?php

namespace AKlump\Drupal\ConfigFixer;

class ConfigFixer {

  protected $data;

  /**
   * @param array $data
   *
   * @see \AKlump\Drupal\ConfigIO::load()
   */
  public function __construct(array &$data) {
    $this->data =& $data;
  }

  /**
   * @param $insert_module
   * @param $after_module
   *
   * @return $this
   */
  public function enableModule($insert_module, $after_module): self {
    $this->data['module'] = $this->data['module'] ?? [];
    if (!array_key_exists($insert_module, $this->data['module'])) {
      $this->data['module'] = array_insert($this->data['module'], [$insert_module => 0], $after_module);
    }

    return $this;
  }

  /**
   * @param string $module
   * @param $after_module
   *
   * @return $this
   */
  public function addDependency(string $module, $after_module = NULL): self {
    $this->data['dependencies'] += ['module' => []];
    if (!in_array($module, $this->data['dependencies']['module'])) {
      $this->insertAfterArrayValue($this->data['dependencies']['module'], $module, $after_module);
    }

    return $this;
  }

  /**
   * @param string $module
   * @param $after_module
   *
   * @return $this
   */
  public function removeDependency(string $module): self {
    $this->data['dependencies'] += ['module' => []];
    $key = array_search($module, $this->data['dependencies']['module']);
    if (FALSE !== $key) {
      unset($this->data['dependencies']['module'][$key]);
      $this->data['dependencies']['module'] = array_values($this->data['dependencies']['module']);
    }

    return $this;
  }

  /**
   * @param string $module
   * @param $after_module
   *
   * @return $this
   */
  public function removePermission(string $module): self {
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
  public function addPermission(string $permission, $after_permission = NULL): self {
    $this->data['permissions'] = $this->data['permissions'] ?? [];
    if (!in_array($permission, $this->data['permissions'])) {
      $this->insertAfterArrayValue($this->data['permissions'], $permission, $after_permission);
    }

    return $this;
  }

  private function insertAfterArrayValue(array &$array, $value, $after_value) {
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
