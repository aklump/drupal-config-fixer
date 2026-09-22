<?php

namespace AKlump\Drupal\ConfigFixer;

class ConfigFixer {

  /** @var string */
  private $basePath;

  public function __construct(string $base_path) {
    $this->setBasePath($base_path);
  }

  public function getBasePath(): string {
    return $this->basePath;
  }

  public function setBasePath(string $basePath): self {
    $this->basePath = $basePath;

    return $this;
  }

  public function modules(): Modules {
    return new Modules($this->getBasePath());
  }

  public function roles(array $roles): Roles {
    return new Roles($this->getBasePath(), $roles);
  }

  /**
   * @param string[] $names
   *   Config entity names, e.g. "block.block.foo" or "views.view.content".
   */
  public function config(array $names): ConfigEntities {
    return new ConfigEntities($this->getBasePath(), $names);
  }

  public function files(): Files {
    return new Files($this->getBasePath());
  }

}
