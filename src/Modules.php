<?php

namespace AKlump\Drupal\ConfigFixer;

use AKlump\Drupal\ConfigFixer\Helpers\SortModules;
use AKlump\Drupal\ConfigFixer\Helpers\VerifyOrder;
use AKlump\Drupal\ConfigFixer\Traits\FileIOTrait;

/**
 * Edit the module list in core.extension.yml.
 */
class Modules {

  use FileIOTrait;

  const PATH = 'core.extension.yml';

  public function __construct(string $base_path) {
    $this->setBasePath($base_path);
  }

  /**
   * List $module as enabled, with weight 0.
   *
   * The list is sorted by weight and then name, as Drupal saves it. A module
   * that is already listed keeps its weight, and the file is not rewritten.
   *
   * @param string $module
   *
   * @return $this
   *
   * @throws \AKlump\Drupal\ConfigFixer\Exception\DrupalFormatMismatchException
   *   If the exported list is not in the order this library sorts it.
   */
  public function enable(string $module): self {
    $data = $this->load(self::PATH);
    if (array_key_exists($module, $data['module'] ?? [])) {
      return $this;
    }
    $data['module'] = $data['module'] ?? [];
    $names = array_keys($data['module']);
    (new VerifyOrder())(self::PATH . ': module names', $names, array_keys((new SortModules())($data['module'])));
    $data['module'][$module] = 0;
    $data['module'] = (new SortModules())($data['module']);
    $this->save(self::PATH, $data);

    return $this;
  }

  /**
   * Remove $module from the list; the file is not rewritten if it is absent.
   *
   * @param string $module
   *
   * @return $this
   */
  public function disable(string $module): self {
    $data = $this->load(self::PATH);
    if (!array_key_exists($module, $data['module'] ?? [])) {
      return $this;
    }
    unset($data['module'][$module]);
    $this->save(self::PATH, $data);

    return $this;
  }

}
