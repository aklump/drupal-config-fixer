<?php

namespace AKlump\DrupalConfigFixer\Tests\Unit\Helpers;

use AKlump\Drupal\ConfigFixer\Helpers\RemoveDependency;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\Drupal\ConfigFixer\Helpers\RemoveDependency
 */
class RemoveDependencyTest extends TestCase {

  public function dataForTestInvokeProvider() {
    $tests = [];
    $tests[] = [
      ['dependencies' => ['module' => ['a', 'c']]],
      ['dependencies' => ['module' => ['a', 'b', 'c']]],
      'b',
      'module',
    ];
    // The last name of a type removes the type.
    $tests[] = [
      ['dependencies' => ['config' => ['x']]],
      ['dependencies' => ['config' => ['x'], 'module' => ['b']]],
      'b',
      'module',
    ];
    // The last dependency leaves an empty mapping, as Drupal writes it.
    $tests[] = [
      ['id' => 'editor', 'dependencies' => []],
      ['id' => 'editor', 'dependencies' => ['module' => ['b']]],
      'b',
      'module',
    ];
    // Missing: unchanged.
    $tests[] = [
      ['dependencies' => ['module' => ['a']]],
      ['dependencies' => ['module' => ['a']]],
      'b',
      'module',
    ];
    $tests[] = [['id' => 'editor'], ['id' => 'editor'], 'b', 'module'];
    $tests[] = [
      ['dependencies' => ['module' => ['b']]],
      ['dependencies' => ['module' => ['b'], 'theme' => ['b']]],
      'b',
      'theme',
    ];

    return $tests;
  }

  /**
   * @dataProvider dataForTestInvokeProvider
   */
  public function testInvoke(array $expected, array $data, string $dependency, string $type) {
    $this->assertSame($expected, (new RemoveDependency())($data, $dependency, $type));
  }

}
