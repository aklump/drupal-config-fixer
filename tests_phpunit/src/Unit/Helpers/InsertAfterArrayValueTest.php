<?php

namespace AKlump\DrupalConfigFixer\Tests\Unit\Helpers;

use AKlump\Drupal\ConfigFixer\Helpers\InsertAfterArrayValue;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\Drupal\ConfigFixer\Helpers\InsertAfterArrayValue
 */
class InsertAfterArrayValueTest extends TestCase {

  public function dataFortestInvokeProvider() {
    $tests = [];
    $tests[] = [
      ['bar', 'foo'],
      ['bar'],
      'foo',
      NULL,
    ];
    $tests[] = [
      ['foo', 'bar'],
      ['foo'],
      'bar',
      'foo',
    ];

    return $tests;
  }

  /**
   * @dataProvider dataFortestInvokeProvider
   */
  public function testInvoke(array $expected, array $array, $value, $after_value) {
    (new InsertAfterArrayValue())($array, $value, $after_value);
    $this->assertSame($expected, $array);
  }

}
