<?php

namespace AKlump\Drupal\ConfigFixer\Helpers;

use Symfony\Component\Yaml\Yaml;

class LoadFile {

  public function __invoke(string $path) {
    // An empty file parses to NULL.
    return Yaml::parseFile($path) ?? [];
  }

}
