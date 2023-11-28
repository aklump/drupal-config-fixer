<?php

namespace AKlump\Drupal\ConfigFixer;

use AKlump\Drupal\ConfigFixer\Traits\FileIOTrait;

class Files {

  use FileIOTrait;

  public function __construct(string $base_path) {
    $this->setBasePath($base_path);
  }

  /**
   * GIT restore a deleted file.
   *
   * @param string $relative_path
   *
   * @return $this
   */
  public function restore(string $relative_path): self {
    exec(sprintf('git restore %s/%s', $this->getBasePath(), $relative_path));

    return $this;
  }

  /**
   * Delete a file.
   *
   * @param $relative_path
   *
   * @return $this
   */
  public function delete($relative_path): self {
    $path = $this->getBasePath() . "/$relative_path";
    if (file_exists($path)) {
      unlink($path);
    }

    return $this;
  }

}
