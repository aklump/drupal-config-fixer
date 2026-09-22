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
 * @uses \AKlump\Drupal\ConfigFixer\Helpers\SortModules
 * @uses \AKlump\Drupal\ConfigFixer\Helpers\DumpYaml
 * @uses \AKlump\Drupal\ConfigFixer\Helpers\VerifyDrupalFormat
 * @uses \AKlump\Drupal\ConfigFixer\Helpers\VerifyOrder
 */
class ModulesTest extends TestCase {

  use TempConfigDirectoryTrait;

  private function getModules(array $core_extension): Modules {
    $this->writeYaml('core.extension.yml', $core_extension);

    return new Modules($this->getTempConfigDirectory());
  }

  private function readModules(): array {
    return $this->readYaml('core.extension.yml')['module'];
  }

  public function testConstructorSetsBasePath() {
    $this->assertSame('/foo', (new Modules('/foo'))->getBasePath());
  }

  public function testEnableSortsLikeDrupal() {
    $modules = $this->getModules(['module' => ['views' => -10, 'help' => 0, 'node' => 0, 'standard' => 1000]]);
    $this->assertSame($modules, $modules->enable('block'));
    $this->assertSame(['views' => -10, 'block' => 0, 'help' => 0, 'node' => 0, 'standard' => 1000], $this->readModules());
  }

  public function testEnableIgnoresLegacyAfterArgument() {
    $this->getModules(['module' => ['help' => 0, 'node' => 0]])->enable('block', 'node');
    $this->assertSame(['block', 'help', 'node'], array_keys($this->readModules()));
  }

  public function testEnableWithoutModuleKeyCreatesIt() {
    $this->getModules(['theme' => ['olivero' => 0]])->enable('foo');
    $data = $this->readYaml('core.extension.yml');
    $this->assertSame(['foo' => 0], $data['module']);
    $this->assertSame(['olivero' => 0], $data['theme']);
  }

  public function testEnableAlreadyEnabledKeepsWeightAndDoesNotRewrite() {
    $this->getModules(['module' => ['foo' => 0, 'bar' => 5]]);
    $this->backdateFile('core.extension.yml');
    (new Modules($this->getTempConfigDirectory()))->enable('bar');
    $this->assertFileNotRewritten('core.extension.yml');
    $this->assertSame(['foo' => 0, 'bar' => 5], $this->readModules());
  }

  public function testEnableIsChainable() {
    $this->getModules(['module' => ['help' => 0]])
      ->enable('honeypot')
      ->enable('captcha');
    $this->assertSame(['captcha', 'help', 'honeypot'], array_keys($this->readModules()));
  }

  public function testDisableRemovesModule() {
    $modules = $this->getModules(['module' => ['foo' => 0, 'bar' => 0]]);
    $this->assertSame($modules, $modules->disable('foo'));
    $this->assertSame(['bar' => 0], $this->readModules());
  }

  public function testDisableMissingModuleDoesNotRewrite() {
    $this->getModules(['module' => ['foo' => 0]]);
    $this->backdateFile('core.extension.yml');
    (new Modules($this->getTempConfigDirectory()))->disable('bar');
    $this->assertFileNotRewritten('core.extension.yml');
  }

  public function testDisableOnEmptyFileDoesNotFail() {
    file_put_contents($this->getTempConfigDirectory() . '/core.extension.yml', '');
    (new Modules($this->getTempConfigDirectory()))->disable('foo');
    $this->assertSame('', file_get_contents($this->getTempConfigDirectory() . '/core.extension.yml'));
  }

  public function testEnableOnEmptyFileWritesModuleList() {
    file_put_contents($this->getTempConfigDirectory() . '/core.extension.yml', '');
    (new Modules($this->getTempConfigDirectory()))->enable('node');
    $this->assertSame("module:\n  node: 0\n", file_get_contents($this->getTempConfigDirectory() . '/core.extension.yml'));
  }

}
