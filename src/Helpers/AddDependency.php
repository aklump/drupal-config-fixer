<?php

namespace AKlump\Drupal\ConfigFixer\Helpers;

/**
 * Add a dependency to a config entity's data, placed as Drupal places it.
 *
 * @see \Drupal\Core\Entity\DependencyTrait::addDependency()
 */
class AddDependency {

  /**
   * The order of the "dependencies" keys in Drupal's config schema.
   */
  const TYPE_ORDER = ['config', 'content', 'module', 'theme', 'enforced'];

  /**
   * The keys a config entity's "dependencies" key follows in Drupal's schema.
   */
  const KEYS_BEFORE_DEPENDENCIES = ['uuid', 'langcode', 'status'];

  /**
   * @param array $data
   *   The config entity's data.
   * @param string $dependency
   *   The name of the dependency, e.g. a module name.
   * @param string $type
   *   The type of dependency: config, content, module or theme.
   *
   * @return array
   *   $data with the dependency added. Names are sorted and type keys are in
   *   schema order, as Drupal writes them.
   */
  public function __invoke(array $data, string $dependency, string $type = 'module'): array {
    if (!array_key_exists('dependencies', $data)) {
      $data = $this->insertDependenciesKey($data);
    }
    $dependencies = $data['dependencies'] ?: [];
    $names = $dependencies[$type] ?? [];
    if (in_array($dependency, $names)) {
      return $data;
    }
    $sorted = $names;
    sort($sorted, SORT_FLAG_CASE);
    (new VerifyOrder())("dependencies.$type names", $names, $sorted);
    (new VerifyOrder())('dependencies types', array_keys($dependencies), array_keys($this->sortTypes($dependencies)));
    $names[] = $dependency;
    sort($names, SORT_FLAG_CASE);
    $dependencies[$type] = $names;
    $data['dependencies'] = $this->sortTypes($dependencies);

    return $data;
  }

  private function insertDependenciesKey(array $data): array {
    $position = 0;
    foreach (array_keys($data) as $index => $key) {
      if (in_array($key, self::KEYS_BEFORE_DEPENDENCIES, TRUE)) {
        $position = $index + 1;
      }
    }

    return array_slice($data, 0, $position, TRUE)
      + ['dependencies' => []]
      + array_slice($data, $position, NULL, TRUE);
  }

  private function sortTypes(array $dependencies): array {
    $order = array_intersect_key(array_flip(self::TYPE_ORDER), $dependencies);

    return array_replace($order, $dependencies);
  }

}
