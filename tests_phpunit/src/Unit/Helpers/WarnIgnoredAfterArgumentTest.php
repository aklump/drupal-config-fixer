<?php

namespace AKlump\DrupalConfigFixer\Tests\Unit\Helpers;

use AKlump\Drupal\ConfigFixer\Helpers\WarnIgnoredAfterArgument;
use AKlump\DrupalConfigFixer\Tests\TestTraits\CaptureWarningsTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\Drupal\ConfigFixer\Helpers\WarnIgnoredAfterArgument
 */
class WarnIgnoredAfterArgumentTest extends TestCase {

  use CaptureWarningsTrait;

  public function testInvokeTriggersWarningNamingTheMethod() {
    $warnings = $this->captureWarnings(function () {
      (new WarnIgnoredAfterArgument())('Foo::bar');
    });
    $this->assertCount(1, $warnings);
    $this->assertStringStartsWith('Foo::bar(): the second ("after") argument is ignored; items are placed in Drupal\'s sort order. Remove the argument', $warnings[0]);
  }

  public function testWarningNamesTheCallersLine() {
    $caller = new class {

      public function method() {
        (new WarnIgnoredAfterArgument())('Foo::bar');
      }

    };
    $line = __LINE__ + 2;
    $warnings = $this->captureWarnings(function () use ($caller) {
      $caller->method();
    });
    $this->assertStringEndsWith(sprintf(' (called in %s on line %d).', __FILE__, $line), $warnings[0]);
  }

}
