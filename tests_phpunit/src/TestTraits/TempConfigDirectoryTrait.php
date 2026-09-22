<?php

namespace AKlump\DrupalConfigFixer\Tests\TestTraits;

use Symfony\Component\Yaml\Yaml;

/**
 * Gives each test its own config directory, removed after the test.
 */
trait TempConfigDirectoryTrait {

  private $tempConfigDirectory = '';

  protected function getTempConfigDirectory(): string {
    if (!$this->tempConfigDirectory) {
      $path = sys_get_temp_dir() . '/drupal_config_fixer_' . uniqid('', TRUE);
      mkdir($path, 0777, TRUE);
      // Resolve symlinks (e.g. macOS /var) so git sees the same path as getcwd().
      $this->tempConfigDirectory = realpath($path);
    }

    return $this->tempConfigDirectory;
  }

  protected function writeYaml(string $relative_path, array $data): string {
    $path = $this->getTempConfigDirectory() . "/$relative_path";
    file_put_contents($path, Yaml::dump($data, 4, 2));

    return $path;
  }

  protected function readYaml(string $relative_path): array {
    return Yaml::parseFile($this->getTempConfigDirectory() . "/$relative_path");
  }

  protected function tearDown(): void {
    if ($this->tempConfigDirectory && is_dir($this->tempConfigDirectory)) {
      exec(sprintf('rm -rf %s', escapeshellarg($this->tempConfigDirectory)));
    }
    $this->tempConfigDirectory = '';
    parent::tearDown();
  }

}
