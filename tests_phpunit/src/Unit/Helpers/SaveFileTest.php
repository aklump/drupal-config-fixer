<?php

namespace AKlump\DrupalConfigFixer\Tests\Unit\Helpers;

use AKlump\Drupal\ConfigFixer\Helpers\SaveFile;
use AKlump\DrupalConfigFixer\Tests\TestTraits\TempConfigDirectoryTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\Drupal\ConfigFixer\Helpers\SaveFile
 */
class SaveFileTest extends TestCase {

  use TempConfigDirectoryTrait;

  public function testInvokeWritesYamlWithTwoSpaceIndent() {
    $path = $this->getTempConfigDirectory() . '/foo.yml';
    (new SaveFile())($path, [
      'dependencies' => ['module' => ['foo', 'bar']],
    ]);
    $this->assertSame("dependencies:\n  module:\n    - foo\n    - bar\n", file_get_contents($path));
  }

  public function testInvokeInlinesBeyondFourLevels() {
    $path = $this->getTempConfigDirectory() . '/foo.yml';
    (new SaveFile())($path, ['a' => ['b' => ['c' => ['d' => ['e' => 1]]]]]);
    $this->assertSame("a:\n  b:\n    c:\n      d: { e: 1 }\n", file_get_contents($path));
  }

  public function testInvokeOverwritesExistingFile() {
    $path = $this->getTempConfigDirectory() . '/foo.yml';
    file_put_contents($path, "old: value\n");
    (new SaveFile())($path, ['new' => 'value']);
    $this->assertSame("new: value\n", file_get_contents($path));
  }

}
