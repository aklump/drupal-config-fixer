<?php

namespace AKlump\Drupal\ConfigFixer\Traits;

use AKlump\Drupal\ConfigFixer\Helpers\LoadFile;
use AKlump\Drupal\ConfigFixer\Helpers\SaveFile;

trait FileIOTrait {

  private string $basePath;

  public function getBasePath(): string {
    return $this->basePath;
  }

  public function setBasePath(string $basePath): self {
    $this->basePath = $basePath;

    return $this;
  }

  public function load(string $relative_path): array {
    return (new LoadFile())($this->getBasePath() . "/$relative_path");
  }

  public function save(string $relative_path, array $data): void {
    (new SaveFile())($this->getBasePath() . "/$relative_path", $data);
  }

}
