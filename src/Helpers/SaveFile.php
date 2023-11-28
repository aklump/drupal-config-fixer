<?php

namespace AKlump\Drupal\ConfigFixer\Helpers;

use Symfony\Component\Yaml\Yaml;

class SaveFile {

  public function __invoke(string $path, array $data) {
    file_put_contents($path, Yaml::dump($data, 4, 2));
  }

}
