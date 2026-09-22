<?php

namespace AKlump\Drupal\ConfigFixer\Traits;

use AKlump\Drupal\ConfigFixer\Helpers\LoadFile;
use AKlump\Drupal\ConfigFixer\Helpers\SaveFile;
use AKlump\Drupal\ConfigFixer\Helpers\VerifyDrupalFormat;

trait FileIOTrait {

  /** @var string */
  private $basePath;

  /**
   * The contents of each loaded file as read, keyed by relative path.
   *
   * @var string[]
   */
  private $exported = [];

  public function getBasePath(): string {
    return $this->basePath;
  }

  public function setBasePath(string $basePath): self {
    $this->basePath = $basePath;

    return $this;
  }

  public function load(string $relative_path): array {
    $path = $this->getBasePath() . "/$relative_path";
    $data = (new LoadFile())($path);
    $this->exported[$relative_path] = file_get_contents($path);

    return $data;
  }

  /**
   * @throws \AKlump\Drupal\ConfigFixer\Exception\DrupalFormatMismatchException
   *   If the file as loaded is not in the format this library writes; the file
   *   is then left untouched.
   */
  public function save(string $relative_path, array $data): void {
    if (isset($this->exported[$relative_path])) {
      (new VerifyDrupalFormat())($relative_path, $this->exported[$relative_path]);
    }
    (new SaveFile())($this->getBasePath() . "/$relative_path", $data);
    unset($this->exported[$relative_path]);
  }

}
