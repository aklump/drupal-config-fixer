<?php

namespace AKlump\DrupalConfigFixer\Tests\Unit\Helpers;

use AKlump\Drupal\ConfigFixer\Helpers\SaveFile;
use AKlump\DrupalConfigFixer\Tests\TestTraits\TempConfigDirectoryTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\Drupal\ConfigFixer\Helpers\SaveFile
 * @covers \AKlump\Drupal\ConfigFixer\Helpers\DumpYaml
 * @uses \AKlump\Drupal\ConfigFixer\Helpers\VerifyDrupalFormat
 * @uses \AKlump\Drupal\ConfigFixer\Helpers\VerifyOrder
 */
class SaveFileTest extends TestCase {

  use TempConfigDirectoryTrait;

  private function save(array $data): string {
    $path = $this->getTempConfigDirectory() . '/foo.yml';
    (new SaveFile())($path, $data);

    return file_get_contents($path);
  }

  public function testInvokeWritesYamlWithTwoSpaceIndent() {
    $this->assertSame("dependencies:\n  module:\n    - foo\n    - bar\n", $this->save([
      'dependencies' => ['module' => ['foo', 'bar']],
    ]));
  }

  public function testInvokeNeverInlinesNestedData() {
    $this->assertSame("a:\n  b:\n    c:\n      d:\n        e: 1\n", $this->save(['a' => ['b' => ['c' => ['d' => ['e' => 1]]]]]));
  }

  public function testInvokeWritesEmptyMappingLikeDrupal() {
    $this->assertSame("dependencies: {  }\n", $this->save(['dependencies' => []]));
  }

  public function testInvokeWritesMultiLineStringAsLiteralBlock() {
    $this->assertSame("body: |-\n  line one\n  line two", $this->save(['body' => "line one\nline two"]));
  }

  public function testInvokeOverwritesExistingFile() {
    $path = $this->getTempConfigDirectory() . '/foo.yml';
    file_put_contents($path, "old: value\n");
    (new SaveFile())($path, ['new' => 'value']);
    $this->assertSame("new: value\n", file_get_contents($path));
  }

}
