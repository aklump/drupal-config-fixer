<?php

namespace AKlump\Drupal\ConfigFixer;

use AKlump\Drupal\ConfigFixer\Helpers\AddDependency;
use AKlump\Drupal\ConfigFixer\Helpers\InsertAfterArrayValue;
use AKlump\Drupal\ConfigFixer\Traits\FileIOTrait;

class Roles {

  use FileIOTrait;

  /** @var array */
  private $roles;

  public function __construct(string $base_path, array $roles) {
    $this->setBasePath($base_path);
    $this->roles = $roles;
  }

  public function removePermission(string $permission): self {
    foreach ($this->roles as $role) {
      $path = "user.role.$role.yml";
      $data = $this->load($path);
      if (empty($data['permissions'])) {
        continue;
      }
      $before = $data;
      $key = array_search($permission, $data['permissions']);
      if (FALSE !== $key) {
        unset($data['permissions'][$key]);
        $data['permissions'] = array_values($data['permissions']);
      }
      if ($data !== $before) {
        $this->save($path, $data);
      };
    }

    return $this;
  }

  /**
   * @param string $module
   * @param $after_module
   *
   * @return $this
   */
  public function addDependency(string $dependency, $after_module = NULL): self {
    foreach ($this->roles as $role) {
      $path = "user.role.$role.yml";
      $data = $this->load($path);
      $before = $data;
      $data = (new AddDependency())($data, $dependency, $after_module, 'module');
      if ($data !== $before) {
        $this->save($path, $data);
      }
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
    foreach ($this->roles as $role) {
      $path = "user.role.$role.yml";
      $data = $this->load($path);
      $before = $data;
      $data['permissions'] = $data['permissions'] ?? [];
      if (!in_array($permission, $data['permissions'])) {
        (new InsertAfterArrayValue())($data['permissions'], $permission, $after_permission);
      }
      if ($before !== $data) {
        $this->save($path, $data);
      }
    }

    return $this;
  }

  public function removeDependency(string $dependency): self {
    foreach ($this->roles as $role) {
      $path = "user.role.$role.yml";
      $data = $this->load($path);
      if (empty($data['dependencies']['module'])) {
        continue;
      }
      $before = $data;
      $key = array_search($dependency, $data['dependencies']['module']);
      if (FALSE !== $key) {
        unset($data['dependencies']['module'][$key]);
        $data['dependencies']['module'] = array_values($data['dependencies']['module']);
      }
      if ($before !== $data) {
        $this->save($path, $data);
      }
    }

    return $this;
  }

}
