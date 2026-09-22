<?php

namespace AKlump\DrupalConfigFixer\Tests\Unit;

use AKlump\Drupal\ConfigFixer\Modules;
use AKlump\DrupalConfigFixer\Tests\TestTraits\TempConfigDirectoryTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\Drupal\ConfigFixer\Modules
 * @uses \AKlump\Drupal\ConfigFixer\Traits\FileIOTrait
 * @uses \AKlump\Drupal\ConfigFixer\Helpers\LoadFile
 * @uses \AKlump\Drupal\ConfigFixer\Helpers\SaveFile
 * @uses \AKlump\Drupal\ConfigFixer\Helpers\InsertAfterArrayValue
 */
class ModulesTest extends TestCase {

  use TempConfigDirectoryTrait;

  private function getModules(array $core_extension): Modules {
    $this->writeYaml('core.extension.yml', $core_extension);

    return new Modules($this->getTempConfigDirectory());
  }

  public function testConstructorSetsBasePath() {
    $this->assertSame('/foo', (new Modules('/foo'))->getBasePath());
  }

  public function testEnableInsertsAfterModule() {
    $modules = $this->getModules(['module' => ['foo' => 0, 'bar' => 0]]);
    $this->assertSame($modules, $modules->enable('baz', 'foo'));
    $this->assertSame(['foo', 'baz', 'bar'], array_keys($this->readYaml('core.extension.yml')['module']));
    $this->assertSame(0, $this->readYaml('core.extension.yml')['module']['baz']);
  }

  public function testEnableAfterMissingModuleAppends() {
    $this->getModules(['module' => ['foo' => 0]])->enable('baz', 'missing');
    $this->assertSame(['foo', 'baz'], array_keys($this->readYaml('core.extension.yml')['module']));
  }

  public function testEnableWithoutModuleKeyCreatesIt() {
    $this->getModules(['theme' => ['olivero' => 0]])->enable('foo', 'bar');
    $data = $this->readYaml('core.extension.yml');
    $this->assertSame(['foo' => 0], $data['module']);
    $this->assertSame(['olivero' => 0], $data['theme']);
  }

  public function testEnableAlreadyEnabledModuleKeepsPositionAndWeight() {
    $this->getModules(['module' => ['foo' => 0, 'bar' => 5]])->enable('bar', 'missing');
    $this->assertSame(['foo' => 0, 'bar' => 5], $this->readYaml('core.extension.yml')['module']);
  }

  public function testEnableIsChainable() {
    $this->getModules(['module' => ['help' => 0]])
      ->enable('captcha', 'help')
      ->enable('honeypot', 'captcha');
    $this->assertSame(['help', 'captcha', 'honeypot'], array_keys($this->readYaml('core.extension.yml')['module']));
  }

  public function testDisableRemovesModule() {
    $modules = $this->getModules(['module' => ['foo' => 0, 'bar' => 0]]);
    $this->assertSame($modules, $modules->disable('foo'));
    $this->assertSame(['bar' => 0], $this->readYaml('core.extension.yml')['module']);
  }

  public function testDisableMissingModuleLeavesOthers() {
    $this->getModules(['module' => ['foo' => 0]])->disable('bar');
    $this->assertSame(['foo' => 0], $this->readYaml('core.extension.yml')['module']);
  }

  public function testAddDependencyInsertsAfterModule() {
    $modules = $this->getModules(['dependencies' => ['module' => ['foo', 'bar']]]);
    $this->assertSame($modules, $modules->addDependency('baz', 'foo'));
    $this->assertSame(['foo', 'baz', 'bar'], $this->readYaml('core.extension.yml')['dependencies']['module']);
  }

  public function testAddDependencyWithoutAfterAppends() {
    $this->getModules(['dependencies' => ['module' => ['foo']]])->addDependency('bar');
    $this->assertSame(['foo', 'bar'], $this->readYaml('core.extension.yml')['dependencies']['module']);
  }

  public function testAddDependencyExistingIsNotDuplicated() {
    $this->getModules(['dependencies' => ['module' => ['foo', 'bar']]])->addDependency('foo', 'bar');
    $this->assertSame(['foo', 'bar'], $this->readYaml('core.extension.yml')['dependencies']['module']);
  }

  public function testAddDependencyCreatesModuleKey() {
    $this->getModules(['dependencies' => ['theme' => ['olivero']]])->addDependency('foo');
    $this->assertSame([
      'theme' => ['olivero'],
      'module' => ['foo'],
    ], $this->readYaml('core.extension.yml')['dependencies']);
  }

  public function testAddDependencyCreatesDependenciesKey() {
    $this->getModules(['module' => ['foo' => 0]])->addDependency('foo');
    $this->assertSame(['module' => ['foo']], $this->readYaml('core.extension.yml')['dependencies']);
  }

  public function testRemoveDependencyReindexes() {
    $modules = $this->getModules(['dependencies' => ['module' => ['foo', 'bar', 'baz']]]);
    $this->assertSame($modules, $modules->removeDependency('bar'));
    $this->assertSame(['foo', 'baz'], $this->readYaml('core.extension.yml')['dependencies']['module']);
  }

  public function testRemoveDependencyMissingLeavesOthers() {
    $this->getModules(['dependencies' => ['module' => ['foo']]])->removeDependency('bar');
    $this->assertSame(['foo'], $this->readYaml('core.extension.yml')['dependencies']['module']);
  }

  public function testRemoveDependencyWithoutDependenciesKey() {
    $this->getModules(['module' => ['foo' => 0]])->removeDependency('foo');
    $this->assertSame(['module' => ['foo' => 0]], $this->readYaml('core.extension.yml'));
  }

}
