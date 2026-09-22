<?php

namespace AKlump\DrupalConfigFixer\Tests\Unit;

use AKlump\Drupal\ConfigFixer\ConfigFixer;
use AKlump\Drupal\ConfigFixer\Exception\DrupalFormatMismatchException;
use AKlump\DrupalConfigFixer\Tests\TestTraits\TempConfigDirectoryTrait;
use PHPUnit\Framework\TestCase;

/**
 * A file not written the way Drupal writes it is refused, and left untouched.
 *
 * @covers \AKlump\Drupal\ConfigFixer\ConfigEntities
 * @covers \AKlump\Drupal\ConfigFixer\Roles
 * @covers \AKlump\Drupal\ConfigFixer\Modules
 * @covers \AKlump\Drupal\ConfigFixer\Traits\FileIOTrait
 * @uses \AKlump\Drupal\ConfigFixer\ConfigFixer
 * @uses \AKlump\Drupal\ConfigFixer\Helpers\LoadFile
 * @uses \AKlump\Drupal\ConfigFixer\Helpers\SaveFile
 * @uses \AKlump\Drupal\ConfigFixer\Helpers\DumpYaml
 * @uses \AKlump\Drupal\ConfigFixer\Helpers\AddDependency
 * @uses \AKlump\Drupal\ConfigFixer\Helpers\SortModules
 * @uses \AKlump\Drupal\ConfigFixer\Helpers\VerifyDrupalFormat
 * @uses \AKlump\Drupal\ConfigFixer\Helpers\VerifyOrder
 */
class DrupalFormatCheckTest extends TestCase {

  use TempConfigDirectoryTrait;

  private function writeRaw(string $relative_path, string $contents): string {
    $path = $this->getTempConfigDirectory() . "/$relative_path";
    file_put_contents($path, $contents);

    return $path;
  }

  private function assertRefusedAndUntouched(string $path, string $contents, callable $call, string $message) {
    try {
      $call(new ConfigFixer($this->getTempConfigDirectory()));
      $this->fail('No exception was thrown.');
    }
    catch (DrupalFormatMismatchException $exception) {
      $this->assertStringContainsString($message, $exception->getMessage());
    }
    $this->assertSame($contents, file_get_contents($path));
  }

  public function testUnfamiliarYamlFormatIsRefused() {
    $contents = "id: editor\npermissions:\n    - 'access content'\n";
    $path = $this->writeRaw('user.role.editor.yml', $contents);
    $this->assertRefusedAndUntouched($path, $contents, function (ConfigFixer $fix) {
      $fix->roles(['editor'])->addPermission('access toolbar');
    }, 'user.role.editor.yml: re-saving this file unchanged would alter line 3');
  }

  public function testUnsortedPermissionsAreRefused() {
    $contents = "id: editor\npermissions:\n  - 'set page title'\n  - 'access content'\n";
    $path = $this->writeRaw('user.role.editor.yml', $contents);
    $this->assertRefusedAndUntouched($path, $contents, function (ConfigFixer $fix) {
      $fix->roles(['editor'])->addPermission('access toolbar');
    }, "user.role.editor.yml: permissions are not in the order this library sorts them");
  }

  public function testUnsortedDependenciesAreRefused() {
    $contents = "dependencies:\n  module:\n    - node\n    - block\nid: a\n";
    $path = $this->writeRaw('block.block.a.yml', $contents);
    $this->assertRefusedAndUntouched($path, $contents, function (ConfigFixer $fix) {
      $fix->config(['block.block.a'])->addDependency('captcha');
    }, 'block.block.a.yml: dependencies.module names are not in the order');
  }

  public function testUnsortedModuleListIsRefused() {
    $contents = "module:\n  node: 0\n  block: 0\n";
    $path = $this->writeRaw('core.extension.yml', $contents);
    $this->assertRefusedAndUntouched($path, $contents, function (ConfigFixer $fix) {
      $fix->modules()->enable('help');
    }, 'core.extension.yml: module names are not in the order');
  }

  public function testUnfamiliarFormatIsRefusedOnRemoval() {
    $contents = "# local\nmodule:\n  devel: 0\n  node: 0\n";
    $path = $this->writeRaw('core.extension.yml', $contents);
    $this->assertRefusedAndUntouched($path, $contents, function (ConfigFixer $fix) {
      $fix->modules()->disable('devel');
    }, "core.extension.yml: re-saving this file unchanged would alter line 1");
  }

  public function testAFileThatNeedsNoChangeIsNotChecked() {
    $contents = "# local\nid: editor\npermissions:\n  - 'set page title'\n  - 'access content'\n";
    $path = $this->writeRaw('user.role.editor.yml', $contents);
    (new ConfigFixer($this->getTempConfigDirectory()))->roles(['editor'])->addPermission('access content');
    $this->assertSame($contents, file_get_contents($path));
  }

  public function testOtherRolesAreStillWrittenBeforeTheRefusedOne() {
    $this->writeRaw('user.role.a.yml', "id: a\npermissions:\n  - x\n");
    $contents = "id: b\npermissions:\n  - z\n  - y\n";
    $path = $this->writeRaw('user.role.b.yml', $contents);
    $this->assertRefusedAndUntouched($path, $contents, function (ConfigFixer $fix) {
      $fix->roles(['a', 'b'])->addPermission('w');
    }, 'user.role.b.yml');
    $this->assertSame("id: a\npermissions:\n  - w\n  - x\n", file_get_contents($this->getTempConfigDirectory() . '/user.role.a.yml'));
  }

}
