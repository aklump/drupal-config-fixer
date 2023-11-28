<?php

namespace AKlump\Drupal\ConfigFixer;

class Roles {

  use \AKlump\Drupal\ConfigFixer\Traits\FileIOTrait;

  private array $roles;

  public function __construct(string $base_path, array $roles) {
    $this->setBasePath($base_path);
    $this->roles = $roles;
  }

  public function removePermission(string $permission): self {
    foreach ($this->roles as $role) {
      $path = "user.role.$role.yml";
      $data = $this->load($path);
      if (empty($data['permissions'])) {
        return $this;
      }
      $key = array_search($permission, $data['permissions']);
      if (FALSE !== $key) {
        unset($data['permissions'][$key]);
        $data['permissions'] = array_values($data['permissions']);
      }
      $this->save($path, $data);
    }
    return $this;
  }

  public function removeDependency(string $dependency): self {
    foreach ($this->roles as $role) {
      $path = "user.role.$role.yml";
      $data = $this->load($path);
      if (empty($data['dependencies']['module'])) {
        return $this;
      }
      $key = array_search($dependency, $data['dependencies']['module']);
      if (FALSE !== $key) {
        unset($data['dependencies']['module'][$key]);
        $data['dependencies']['module'] = array_values($data['dependencies']['module']);
      }
      $this->save($path, $data);
    }
    return $this;
  }

}
