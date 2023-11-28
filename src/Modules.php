<?php

namespace AKlump\Drupal\ConfigFixer;

use AKlump\Drupal\ConfigFixer\Helpers\InsertAfterArrayValue;
use AKlump\Drupal\ConfigFixer\Traits\FileIOTrait;

class Modules {

  use FileIOTrait;

  public function __construct(string $base_path) {
    $this->setBasePath($base_path);
  }

  /**
   * @param $insert_module
   * @param $after_module
   *
   * @return $this
   */
  public function enable($insert_module, $after_module): self {
    $path = 'core.extension.yml';
    $data = $this->load($path);
    $data['module'] = $data['module'] ?? [];
    if (!array_key_exists($insert_module, $data['module'])) {
      $data['module'] = array_insert($data['module'], [$insert_module => 0], $after_module);
    }
    $this->save($path, $data);

    return $this;
  }

  /**
   * Disable $module from the config $data.
   *
   * @param string $module
   *
   * @return $this
   */
  public function disable(array $modules): self {
    $path = 'core.extension.yml';
    $data = $this->load($path);
    foreach ($modules as $module) {
      unset($data['module'][$module]);
    }
    $this->save($path, $data);

    return $this;
  }

  /**
   * @param string $module
   * @param $after_module
   *
   * @return $this
   */
  public function addDependency(string $module, $after_module = NULL): self {
    $path = 'core.extension.yml';
    $data = $this->load($path);
    $data['dependencies'] += ['module' => []];
    if (!in_array($module, $data['dependencies']['module'])) {
      (new InsertAfterArrayValue())($data['dependencies']['module'], $module, $after_module);
    }
    $this->save($path, $data);

    return $this;
  }

  /**
   * @param string $module
   * @param $after_module
   *
   * @return $this
   */
  public function removeDependency(string $module): self {
    $path = 'core.extension.yml';
    $data = $this->load($path);
    $data['dependencies'] += ['module' => []];
    $key = array_search($module, $data['dependencies']['module']);
    if (FALSE !== $key) {
      unset($data['dependencies']['module'][$key]);
      $data['dependencies']['module'] = array_values($data['dependencies']['module']);
    }
    $this->save($path, $data);

    return $this;
  }

}
