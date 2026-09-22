<?php

namespace AKlump\DrupalConfigFixer\Tests\Unit\Helpers;

use AKlump\Drupal\ConfigFixer\Helpers\AddDependency;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\Drupal\ConfigFixer\Helpers\AddDependency
 * @uses \AKlump\Drupal\ConfigFixer\Helpers\InsertAfterArrayValue
 */
class AddDependencyTest extends TestCase {

  public function dataFortestInvokeProvider() {
    $tests = [];
    $tests[] = [
      ['dependencies' => ['module' => ['foo', 'bar']]],
      ['dependencies' => ['module' => ['foo']]],
      'bar',
      'foo',
      'module',
    ];
    $tests[] = [
      ['dependencies' => ['module' => ['foo']]],
      [],
      'foo',
      NULL,
      'module',
    ];
    $tests[] = [
      ['dependencies' => ['module' => ['foo', 'bar']]],
      ['dependencies' => ['module' => ['bar']]],
      'foo',
      NULL,
      'module',
    ];
    $tests[] = [
      ['dependencies' => ['lorem' => ['bar']]],
      [],
      'bar',
      NULL,
      'lorem',
    ];
    $tests[] = [
      ['dependencies' => ['module' => ['foo', 'baz', 'bar']]],
      ['dependencies' => ['module' => ['foo', 'bar']]],
      'baz',
      'foo',
      'module',
    ];
    $tests[] = [
      ['dependencies' => ['module' => ['foo', 'bar']]],
      ['dependencies' => ['module' => ['foo', 'bar']]],
      'foo',
      'bar',
      'module',
    ];
    $tests[] = [
      ['dependencies' => ['module' => ['foo', 'bar']]],
      ['dependencies' => ['module' => ['foo']]],
      'bar',
      'missing',
      'module',
    ];
    $tests[] = [
      ['id' => 'editor', 'dependencies' => ['config' => ['x'], 'module' => ['foo']]],
      ['id' => 'editor', 'dependencies' => ['config' => ['x']]],
      'foo',
      NULL,
      'module',
    ];

    return $tests;
  }

  /**
   * @dataProvider dataFortestInvokeProvider
   */
  public function testInvoke($expected, $data, $dependency, $after_item, $type) {
    $result = (new AddDependency())($data, $dependency, $after_item, $type);
    $this->assertSame($expected, $result);
  }
}
