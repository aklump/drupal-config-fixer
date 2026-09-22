<?php

namespace AKlump\DrupalConfigFixer\Tests\Unit;

use AKlump\Drupal\ConfigFixer\Permissions;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\Drupal\ConfigFixer\Permissions
 * @uses \AKlump\Drupal\ConfigFixer\Helpers\InsertAfterArrayValue
 */
class PermissionsTest extends TestCase {

  private function getPermissions(): array {
    $data = \Closure::bind(function () {
      return $this->data;
    }, $this->permissions, Permissions::class)();

    return $data['permissions'] ?? [];
  }

  private $permissions;

  protected function setUp(): void {
    $this->permissions = new Permissions();
  }

  public function testRemoveWithNoPermissionsReturnsSelf() {
    $this->assertSame($this->permissions, $this->permissions->remove('foo'));
    $this->assertSame([], $this->getPermissions());
  }

  public function testAddAppendsAndReturnsSelf() {
    $this->assertSame($this->permissions, $this->permissions->add('a'));
    $this->permissions->add('b');
    $this->assertSame(['a', 'b'], $this->getPermissions());
  }

  public function testAddInsertsAfterPermission() {
    $this->permissions->add('a')->add('c')->add('b', 'a');
    $this->assertSame(['a', 'b', 'c'], $this->getPermissions());
  }

  public function testAddExistingIsNotDuplicated() {
    $this->permissions->add('a')->add('b')->add('a', 'b');
    $this->assertSame(['a', 'b'], $this->getPermissions());
  }

  public function testRemoveReindexes() {
    $this->permissions->add('a')->add('b')->add('c');
    $this->assertSame($this->permissions, $this->permissions->remove('b'));
    $this->assertSame(['a', 'c'], $this->getPermissions());
  }

  public function testRemoveMissingLeavesOthers() {
    $this->permissions->add('a');
    $this->permissions->remove('b');
    $this->assertSame(['a'], $this->getPermissions());
  }

}
