<?php

namespace AKlump\Drupal\ConfigFixer\Helpers;

class SaveFile {

  public function __invoke(string $path, array $data) {
    file_put_contents($path, (new DumpYaml())($data));
  }

}
