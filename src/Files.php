<?php

namespace AKlump\Drupal\ConfigFixer;

use AKlump\Drupal\ConfigFixer\Traits\FileIOTrait;

class Files {

  use FileIOTrait;

  public function __construct(string $base_path) {
    $this->setBasePath($base_path);
  }

  public function delete(array $relative_paths): self {
    foreach ($relative_paths as $relative_path) {
      $path = $this->getBasePath() . "/$relative_path";
      if (!file_exists($path)) {
        continue;
      }
      unlink($path);
    }

    return $this;
  }

}
