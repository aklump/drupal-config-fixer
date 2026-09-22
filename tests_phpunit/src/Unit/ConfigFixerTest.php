<?php

namespace AKlump\DrupalConfigFixer\Tests\Unit;

use AKlump\Drupal\ConfigFixer\ConfigFixer;
use AKlump\Drupal\ConfigFixer\Files;
use AKlump\Drupal\ConfigFixer\Modules;
use AKlump\Drupal\ConfigFixer\Roles;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\Drupal\ConfigFixer\ConfigFixer
 * @uses \AKlump\Drupal\ConfigFixer\Files
 * @uses \AKlump\Drupal\ConfigFixer\Modules
 * @uses \AKlump\Drupal\ConfigFixer\Roles
 * @uses \AKlump\Drupal\ConfigFixer\Traits\FileIOTrait
 */
class ConfigFixerTest extends TestCase {

  public function testConstructorSetsBasePath() {
    $fixer = new ConfigFixer('/foo/bar');
    $this->assertSame('/foo/bar', $fixer->getBasePath());
  }

  public function testSetBasePathReturnsSelfAndChangesPath() {
    $fixer = new ConfigFixer('/foo');
    $this->assertSame($fixer, $fixer->setBasePath('/bar'));
    $this->assertSame('/bar', $fixer->getBasePath());
  }

  public function testModulesReturnsModulesWithBasePath() {
    $modules = (new ConfigFixer('/foo'))->modules();
    $this->assertInstanceOf(Modules::class, $modules);
    $this->assertSame('/foo', $modules->getBasePath());
  }

  public function testRolesReturnsRolesWithBasePath() {
    $roles = (new ConfigFixer('/foo'))->roles(['editor']);
    $this->assertInstanceOf(Roles::class, $roles);
    $this->assertSame('/foo', $roles->getBasePath());
  }

  public function testFilesReturnsFilesWithBasePath() {
    $files = (new ConfigFixer('/foo'))->files();
    $this->assertInstanceOf(Files::class, $files);
    $this->assertSame('/foo', $files->getBasePath());
  }

  public function testFactoriesUseCurrentBasePath() {
    $fixer = new ConfigFixer('/foo');
    $fixer->setBasePath('/bar');
    $this->assertSame('/bar', $fixer->modules()->getBasePath());
  }

  public function testEachFactoryCallReturnsNewInstance() {
    $fixer = new ConfigFixer('/foo');
    $this->assertNotSame($fixer->modules(), $fixer->modules());
    $this->assertNotSame($fixer->files(), $fixer->files());
    $this->assertNotSame($fixer->roles([]), $fixer->roles([]));
  }

}
