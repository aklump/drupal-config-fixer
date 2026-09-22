<?php

namespace AKlump\DrupalConfigFixer\Tests\Unit\Helpers;

use AKlump\Drupal\ConfigFixer\Exception\DrupalFormatMismatchException;
use AKlump\Drupal\ConfigFixer\Helpers\VerifyOrder;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\Drupal\ConfigFixer\Helpers\VerifyOrder
 */
class VerifyOrderTest extends TestCase {

  public function testSameOrderPasses() {
    (new VerifyOrder())('permissions', ['a', 'b'], ['a', 'b']);
    (new VerifyOrder())('permissions', [], []);
    $this->addToAssertionCount(1);
  }

  public function testDifferentOrderThrowsWithBothLists() {
    $this->expectException(DrupalFormatMismatchException::class);
    $this->expectExceptionMessage("permissions are not in the order this library sorts them. Not writing the file.\n  exported: b, a\n  would be: a, b");
    (new VerifyOrder())('permissions', ['b', 'a'], ['a', 'b']);
  }

}
