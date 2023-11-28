<?php

namespace AKlump\Drupal\ConfigFixer\Helpers;

use Symfony\Component\Yaml\Yaml;

class LoadFile {

  public function __invoke(string $path) {
    return Yaml::parseFile($path);
  }

}
