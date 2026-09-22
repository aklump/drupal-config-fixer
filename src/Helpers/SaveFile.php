<?php

namespace AKlump\Drupal\ConfigFixer\Helpers;

use Symfony\Component\Yaml\Yaml;

class SaveFile {

  /**
   * Write $data the way Drupal writes exported config.
   *
   * Matches \Drupal\Component\Serialization\YamlSymfony::encode(): nothing is
   * inlined, two-space indent, multi-line strings as literal blocks.
   */
  public function __invoke(string $path, array $data) {
    file_put_contents($path, Yaml::dump($data, PHP_INT_MAX, 2, Yaml::DUMP_EXCEPTION_ON_INVALID_TYPE | Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK));
  }

}
