<?php

namespace AKlump\Drupal\ConfigFixer;

use AKlump\Drupal\ConfigFixer\Helpers\AddDependency;
use AKlump\Drupal\ConfigFixer\Helpers\RemoveDependency;
use AKlump\Drupal\ConfigFixer\Traits\FileIOTrait;

/**
 * Edit one or more config entity files, e.g. block.block.foo.yml.
 */
class ConfigEntities {

  use FileIOTrait;

  /** @var string[] */
  private $names;

  /**
   * @param string $base_path
   * @param string[] $names
   *   Config names, e.g. "block.block.foo"; a trailing ".yml" is allowed.
   */
  public function __construct(string $base_path, array $names) {
    $this->setBasePath($base_path);
    $this->names = array_map(function ($name) {
      return preg_replace('/\.yml$/', '', $name);
    }, $names);
  }

  /**
   * Add a module to each file's dependencies.
   *
   * @param string $module
   *
   * @return $this
   */
  public function addDependency(string $module): self {
    return $this->alter(function (array $data) use ($module) {
      return (new AddDependency())($data, $module, 'module');
    });
  }

  /**
   * Remove a module from each file's dependencies.
   *
   * @param string $module
   *
   * @return $this
   */
  public function removeDependency(string $module): self {
    return $this->alter(function (array $data) use ($module) {
      return (new RemoveDependency())($data, $module, 'module');
    });
  }

  /**
   * Apply $callback to each file's data, saving only the files it changes.
   *
   * @param callable $callback
   *   Receives the file's data and returns the altered data.
   *
   * @return $this
   */
  protected function alter(callable $callback): self {
    foreach ($this->names as $name) {
      $path = "$name.yml";
      $data = $this->load($path);
      $altered = $callback($data);
      if ($altered !== $data) {
        $this->save($path, $altered);
      }
    }

    return $this;
  }

}
