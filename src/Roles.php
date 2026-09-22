<?php

namespace AKlump\Drupal\ConfigFixer;

/**
 * Edit one or more user.role.<role>.yml files.
 */
class Roles extends ConfigEntities {

  /**
   * @param string $base_path
   * @param string[] $roles
   *   Role IDs, e.g. "authenticated".
   */
  public function __construct(string $base_path, array $roles) {
    parent::__construct($base_path, array_map(function ($role) {
      return "user.role.$role";
    }, $roles));
  }

  /**
   * Add a permission to each role.
   *
   * Permissions are kept sorted by name, as Drupal saves them.
   *
   * @param string $permission
   *
   * @return $this
   */
  public function addPermission(string $permission): self {
    return $this->alter(function (array $data) use ($permission) {
      $permissions = $data['permissions'] ?? [];
      if (!in_array($permission, $permissions)) {
        $permissions[] = $permission;
        sort($permissions);
        $data['permissions'] = $permissions;
      }

      return $data;
    });
  }

  /**
   * Remove a permission from each role.
   *
   * @param string $permission
   *
   * @return $this
   */
  public function removePermission(string $permission): self {
    return $this->alter(function (array $data) use ($permission) {
      $key = array_search($permission, $data['permissions'] ?? []);
      if (FALSE !== $key) {
        unset($data['permissions'][$key]);
        $data['permissions'] = array_values($data['permissions']);
      }

      return $data;
    });
  }

}
