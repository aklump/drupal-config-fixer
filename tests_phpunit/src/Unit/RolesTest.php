<?php

namespace AKlump\DrupalConfigFixer\Tests\Unit;

use AKlump\Drupal\ConfigFixer\Roles;
use AKlump\DrupalConfigFixer\Tests\TestTraits\TempConfigDirectoryTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\Drupal\ConfigFixer\Roles
 * @covers \AKlump\Drupal\ConfigFixer\ConfigEntities
 * @uses \AKlump\Drupal\ConfigFixer\Traits\FileIOTrait
 * @uses \AKlump\Drupal\ConfigFixer\Helpers\LoadFile
 * @uses \AKlump\Drupal\ConfigFixer\Helpers\SaveFile
 * @uses \AKlump\Drupal\ConfigFixer\Helpers\AddDependency
 * @uses \AKlump\Drupal\ConfigFixer\Helpers\RemoveDependency
 * @uses \AKlump\Drupal\ConfigFixer\Helpers\DumpYaml
 * @uses \AKlump\Drupal\ConfigFixer\Helpers\VerifyDrupalFormat
 * @uses \AKlump\Drupal\ConfigFixer\Helpers\VerifyOrder
 */
class RolesTest extends TestCase {

  use TempConfigDirectoryTrait;

  /**
   * @param array $roles
   *   Keys are role ids, values are the role config.
   */
  private function getRoles(array $roles): Roles {
    foreach ($roles as $role => $data) {
      $this->writeYaml("user.role.$role.yml", $data);
    }

    return new Roles($this->getTempConfigDirectory(), array_keys($roles));
  }

  private function readRole(string $role): array {
    return $this->readYaml("user.role.$role.yml");
  }

  public function testConstructorSetsBasePath() {
    $this->assertSame('/foo', (new Roles('/foo', []))->getBasePath());
  }

  public function testNoRolesDoesNothing() {
    $roles = new Roles($this->getTempConfigDirectory(), []);
    $this->assertSame($roles, $roles->addPermission('foo'));
    $this->assertSame($roles, $roles->removePermission('foo'));
    $this->assertSame($roles, $roles->addDependency('foo'));
    $this->assertSame($roles, $roles->removeDependency('foo'));
  }

  public function testAddPermissionSortsLikeDrupal() {
    $roles = $this->getRoles(['editor' => ['permissions' => ['access content overview', 'set page title']]]);
    $this->assertSame($roles, $roles->addPermission('skip CAPTCHA'));
    $this->assertSame(['access content overview', 'set page title', 'skip CAPTCHA'], $this->readRole('editor')['permissions']);
  }

  public function testAddPermissionIgnoresLegacyAfterArgument() {
    $this->getRoles(['editor' => ['permissions' => ['a', 'c']]])->addPermission('b', 'c');
    $this->assertSame(['a', 'b', 'c'], $this->readRole('editor')['permissions']);
  }

  public function testAddPermissionCreatesPermissionsKey() {
    $this->getRoles(['editor' => ['id' => 'editor']])->addPermission('a');
    $this->assertSame(['id' => 'editor', 'permissions' => ['a']], $this->readRole('editor'));
  }

  public function testAddPermissionAppliesToAllRoles() {
    $this->getRoles([
      'editor' => ['permissions' => ['a']],
      'admin' => ['permissions' => ['x']],
    ])->addPermission('b');
    $this->assertSame(['a', 'b'], $this->readRole('editor')['permissions']);
    $this->assertSame(['b', 'x'], $this->readRole('admin')['permissions']);
  }

  public function testAddExistingPermissionDoesNotRewrite() {
    $this->getRoles(['editor' => ['permissions' => ['a', 'b']]]);
    $this->backdateFile('user.role.editor.yml');
    (new Roles($this->getTempConfigDirectory(), ['editor']))->addPermission('a');
    $this->assertFileNotRewritten('user.role.editor.yml');
  }

  public function testRemovePermission() {
    $roles = $this->getRoles(['editor' => ['permissions' => ['a', 'b', 'c']]]);
    $this->assertSame($roles, $roles->removePermission('b'));
    $this->assertSame(['a', 'c'], $this->readRole('editor')['permissions']);
  }

  public function testRemoveMissingPermissionDoesNotRewrite() {
    $this->getRoles(['editor' => ['permissions' => ['a']]]);
    $this->backdateFile('user.role.editor.yml');
    (new Roles($this->getTempConfigDirectory(), ['editor']))->removePermission('b');
    $this->assertFileNotRewritten('user.role.editor.yml');
  }

  public function testRemovePermissionContinuesPastRoleWithoutPermissions() {
    $this->getRoles([
      'anonymous' => ['id' => 'anonymous'],
      'authenticated' => ['permissions' => ['a', 'b']],
    ])->removePermission('a');
    $this->assertSame(['id' => 'anonymous'], $this->readRole('anonymous'));
    $this->assertSame(['b'], $this->readRole('authenticated')['permissions']);
  }

  public function testAddDependencyWritesRoleLikeDrupal() {
    $this->getRoles(['editor' => [
      'uuid' => 'abc',
      'langcode' => 'en',
      'status' => TRUE,
      'id' => 'editor',
      'label' => 'Editor',
      'weight' => 2,
      'is_admin' => FALSE,
      'permissions' => ['access content overview'],
    ]])->addDependency('captcha')->addPermission('skip CAPTCHA');
    $this->assertSame(
      "uuid: abc\nlangcode: en\nstatus: true\ndependencies:\n  module:\n    - captcha\nid: editor\nlabel: Editor\nweight: 2\nis_admin: false\npermissions:\n  - 'access content overview'\n  - 'skip CAPTCHA'\n",
      file_get_contents($this->getTempConfigDirectory() . '/user.role.editor.yml')
    );
  }

  public function testAddDependencyIgnoresLegacyAfterArgument() {
    $this->getRoles(['editor' => ['dependencies' => ['module' => ['block', 'node']]]])->addDependency('captcha', 'node');
    $this->assertSame(['block', 'captcha', 'node'], $this->readRole('editor')['dependencies']['module']);
  }

  public function testRemoveDependencyContinuesPastRoleWithoutDependencies() {
    $this->getRoles([
      'anonymous' => ['id' => 'anonymous'],
      'authenticated' => ['dependencies' => ['module' => ['a', 'b']]],
    ])->removeDependency('a');
    $this->assertSame(['id' => 'anonymous'], $this->readRole('anonymous'));
    $this->assertSame(['b'], $this->readRole('authenticated')['dependencies']['module']);
  }

}
