<?php

namespace AKlump\DrupalConfigFixer\Tests\Unit;

use AKlump\Drupal\ConfigFixer\Roles;
use AKlump\DrupalConfigFixer\Tests\TestTraits\TempConfigDirectoryTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\Drupal\ConfigFixer\Roles
 * @uses \AKlump\Drupal\ConfigFixer\Traits\FileIOTrait
 * @uses \AKlump\Drupal\ConfigFixer\Helpers\LoadFile
 * @uses \AKlump\Drupal\ConfigFixer\Helpers\SaveFile
 * @uses \AKlump\Drupal\ConfigFixer\Helpers\AddDependency
 * @uses \AKlump\Drupal\ConfigFixer\Helpers\InsertAfterArrayValue
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

  private function getRoleMTime(string $role): int {
    $path = $this->getTempConfigDirectory() . "/user.role.$role.yml";
    touch($path, 1000000000);
    clearstatcache();

    return filemtime($path);
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

  public function testAddPermissionInsertsAfterPermission() {
    $roles = $this->getRoles(['editor' => ['permissions' => ['a', 'c']]]);
    $this->assertSame($roles, $roles->addPermission('b', 'a'));
    $this->assertSame(['a', 'b', 'c'], $this->readRole('editor')['permissions']);
  }

  public function testAddPermissionWithoutAfterAppends() {
    $this->getRoles(['editor' => ['permissions' => ['a']]])->addPermission('b');
    $this->assertSame(['a', 'b'], $this->readRole('editor')['permissions']);
  }

  public function testAddPermissionCreatesPermissionsKey() {
    $this->getRoles(['editor' => ['id' => 'editor']])->addPermission('a');
    $this->assertSame(['id' => 'editor', 'permissions' => ['a']], $this->readRole('editor'));
  }

  public function testAddPermissionAppliesToAllRoles() {
    $this->getRoles([
      'editor' => ['permissions' => ['a']],
      'admin' => ['permissions' => ['x']],
    ])->addPermission('b', 'a');
    $this->assertSame(['a', 'b'], $this->readRole('editor')['permissions']);
    $this->assertSame(['x', 'b'], $this->readRole('admin')['permissions']);
  }

  public function testAddExistingPermissionDoesNotSave() {
    $roles = $this->getRoles(['editor' => ['permissions' => ['a', 'b']]]);
    $mtime = $this->getRoleMTime('editor');
    $roles->addPermission('a', 'b');
    clearstatcache();
    $this->assertSame($mtime, filemtime($this->getTempConfigDirectory() . '/user.role.editor.yml'));
    $this->assertSame(['a', 'b'], $this->readRole('editor')['permissions']);
  }

  public function testAddPermissionChain() {
    $this->getRoles(['site_admin' => ['permissions' => ['x']]])
      ->addPermission('administer CAPTCHA settings')
      ->addPermission('administer honeypot', 'administer CAPTCHA settings')
      ->addPermission('bypass honeypot protection', 'administer honeypot');
    $this->assertSame([
      'x',
      'administer CAPTCHA settings',
      'administer honeypot',
      'bypass honeypot protection',
    ], $this->readRole('site_admin')['permissions']);
  }

  public function testRemovePermissionReindexes() {
    $roles = $this->getRoles(['editor' => ['permissions' => ['a', 'b', 'c']]]);
    $this->assertSame($roles, $roles->removePermission('b'));
    $this->assertSame(['a', 'c'], $this->readRole('editor')['permissions']);
  }

  public function testRemoveMissingPermissionDoesNotSave() {
    $roles = $this->getRoles(['editor' => ['permissions' => ['a']]]);
    $mtime = $this->getRoleMTime('editor');
    $roles->removePermission('b');
    clearstatcache();
    $this->assertSame($mtime, filemtime($this->getTempConfigDirectory() . '/user.role.editor.yml'));
  }

  public function testRemovePermissionAppliesToAllRoles() {
    $this->getRoles([
      'anonymous' => ['permissions' => ['a', 'b']],
      'authenticated' => ['permissions' => ['b', 'c']],
    ])->removePermission('b');
    $this->assertSame(['a'], $this->readRole('anonymous')['permissions']);
    $this->assertSame(['c'], $this->readRole('authenticated')['permissions']);
  }

  public function testRemovePermissionContinuesPastRoleWithoutPermissions() {
    $this->getRoles([
      'anonymous' => ['id' => 'anonymous'],
      'authenticated' => ['permissions' => ['a', 'b']],
    ])->removePermission('a');
    $this->assertSame(['id' => 'anonymous'], $this->readRole('anonymous'));
    $this->assertSame(['b'], $this->readRole('authenticated')['permissions']);
  }

  public function testAddDependencyInsertsAfterModule() {
    $roles = $this->getRoles(['editor' => ['dependencies' => ['module' => ['block', 'node']]]]);
    $this->assertSame($roles, $roles->addDependency('captcha', 'block'));
    $this->assertSame(['block', 'captcha', 'node'], $this->readRole('editor')['dependencies']['module']);
  }

  public function testAddDependencyWithoutAfterPrepends() {
    $this->getRoles(['editor' => ['dependencies' => ['module' => ['block']]]])->addDependency('captcha');
    $this->assertSame(['captcha', 'block'], $this->readRole('editor')['dependencies']['module']);
  }

  public function testAddDependencyCreatesDependencies() {
    $this->getRoles(['editor' => ['id' => 'editor']])->addDependency('captcha');
    $this->assertSame(['module' => ['captcha']], $this->readRole('editor')['dependencies']);
  }

  public function testAddExistingDependencyDoesNotSave() {
    $roles = $this->getRoles(['editor' => ['dependencies' => ['module' => ['captcha']]]]);
    $mtime = $this->getRoleMTime('editor');
    $roles->addDependency('captcha', 'block');
    clearstatcache();
    $this->assertSame($mtime, filemtime($this->getTempConfigDirectory() . '/user.role.editor.yml'));
  }

  public function testAddDependencyAppliesToAllRoles() {
    $this->getRoles([
      'editor' => ['dependencies' => ['module' => ['block']]],
      'admin' => ['dependencies' => ['module' => ['node']]],
    ])->addDependency('captcha', 'block');
    $this->assertSame(['block', 'captcha'], $this->readRole('editor')['dependencies']['module']);
    $this->assertSame(['node', 'captcha'], $this->readRole('admin')['dependencies']['module']);
  }

  public function testRemoveDependencyReindexes() {
    $roles = $this->getRoles(['editor' => ['dependencies' => ['module' => ['a', 'b', 'c']]]]);
    $this->assertSame($roles, $roles->removeDependency('b'));
    $this->assertSame(['a', 'c'], $this->readRole('editor')['dependencies']['module']);
  }

  public function testRemoveMissingDependencyDoesNotSave() {
    $roles = $this->getRoles(['editor' => ['dependencies' => ['module' => ['a']]]]);
    $mtime = $this->getRoleMTime('editor');
    $roles->removeDependency('b');
    clearstatcache();
    $this->assertSame($mtime, filemtime($this->getTempConfigDirectory() . '/user.role.editor.yml'));
  }

  public function testRemoveDependencyAppliesToAllRoles() {
    $this->getRoles([
      'anonymous' => ['dependencies' => ['module' => ['a', 'b']]],
      'authenticated' => ['dependencies' => ['module' => ['b']]],
    ])->removeDependency('b');
    $this->assertSame(['a'], $this->readRole('anonymous')['dependencies']['module']);
    $this->assertSame([], $this->readRole('authenticated')['dependencies']['module']);
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
