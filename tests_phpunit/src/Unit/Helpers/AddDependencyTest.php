<?php

namespace AKlump\DrupalConfigFixer\Tests\Unit\Helpers;

use AKlump\Drupal\ConfigFixer\Helpers\AddDependency;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\Drupal\ConfigFixer\Helpers\AddDependency
 */
class AddDependencyTest extends TestCase {

  public function dataFortestInvokeProvider() {
    $tests = [];
    // Names are sorted.
    $tests[] = [
      ['dependencies' => ['module' => ['bar', 'foo']]],
      ['dependencies' => ['module' => ['foo']]],
      'bar',
      'module',
    ];
    // Already present: unchanged.
    $tests[] = [
      ['dependencies' => ['module' => ['foo', 'bar']]],
      ['dependencies' => ['module' => ['foo', 'bar']]],
      'foo',
      'module',
    ];
    // No dependencies key and no key it follows: it goes first.
    $tests[] = [
      ['dependencies' => ['module' => ['foo']], 'id' => 'editor'],
      ['id' => 'editor'],
      'foo',
      'module',
    ];
    // A new dependencies key goes after uuid, langcode and status.
    $tests[] = [
      [
        'uuid' => 'x',
        'langcode' => 'en',
        'status' => TRUE,
        'dependencies' => ['module' => ['foo']],
        'id' => 'editor',
        'permissions' => [],
      ],
      [
        'uuid' => 'x',
        'langcode' => 'en',
        'status' => TRUE,
        'id' => 'editor',
        'permissions' => [],
      ],
      'foo',
      'module',
    ];
    // Type keys are in schema order.
    $tests[] = [
      ['dependencies' => ['config' => ['a'], 'module' => ['foo'], 'theme' => ['t']]],
      ['dependencies' => ['theme' => ['t'], 'config' => ['a']]],
      'foo',
      'module',
    ];
    // An empty dependencies mapping.
    $tests[] = [
      ['langcode' => 'en', 'dependencies' => ['theme' => ['olivero']]],
      ['langcode' => 'en', 'dependencies' => []],
      'olivero',
      'theme',
    ];

    return $tests;
  }

  /**
   * @dataProvider dataFortestInvokeProvider
   */
  public function testInvoke($expected, $data, $dependency, $type) {
    $result = (new AddDependency())($data, $dependency, $type);
    $this->assertSame($expected, $result);
  }

  public function testTypeDefaultsToModule() {
    $this->assertSame(['dependencies' => ['module' => ['foo']]], (new AddDependency())([], 'foo'));
  }

}
