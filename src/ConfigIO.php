<?php

namespace AKlump\Drupal\ConfigFixer;

use Symfony\Component\Yaml\Yaml;

final class ConfigIO {

  private string $path;

  public function __construct(string $path) {
    $this->path = $path;
  }

  public function load(): array {
    return Yaml::parseFile($this->path);
  }

  public function save(array $data) {
    file_put_contents($this->path, Yaml::dump($data, 4, 2));
  }
}
