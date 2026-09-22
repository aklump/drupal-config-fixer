<?php

namespace AKlump\DrupalConfigFixer\Tests\Unit\Helpers;

use AKlump\Drupal\ConfigFixer\Helpers\SortModules;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\Drupal\ConfigFixer\Helpers\SortModules
 */
class SortModulesTest extends TestCase {

  public function dataForTestInvokeProvider() {
    $tests = [];
    $tests[] = [
      ['block' => 0, 'help' => 0, 'node' => 0],
      ['node' => 0, 'block' => 0, 'help' => 0],
    ];
    $tests[] = [
      ['views' => -10, 'block' => 0, 'standard' => 1000],
      ['standard' => 1000, 'block' => 0, 'views' => -10],
    ];
    // Drupal pads the absolute weight, so among negative weights -5 sorts
    // before -10. This mirrors core exactly rather than ordering numerically.
    $tests[] = [
      ['a' => -5, 'b' => -10, 'c' => 0],
      ['a' => -5, 'c' => 0, 'b' => -10],
    ];
    $tests[] = [[], []];

    return $tests;
  }

  /**
   * @dataProvider dataForTestInvokeProvider
   */
  public function testInvoke(array $expected, array $modules) {
    $this->assertSame($expected, (new SortModules())($modules));
  }

}
