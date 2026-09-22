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
   *   May contain wildcards, e.g. "monolog*.*"; git matches them as a pathspec.
   *
   * @return $this
   *
   * @throws \RuntimeException
   *   If git fails, e.g. the path matches no file known to git.
   */
  public function restore(string $relative_path): self {
    $path = $this->getBasePath() . "/$relative_path";
    exec(sprintf('git restore -- %s 2>&1', escapeshellarg($path)), $output, $exit_code);
    if ($exit_code !== 0) {
      throw new \RuntimeException(sprintf('Could not restore "%s": %s', $path, implode(PHP_EOL, $output)));
    }

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
