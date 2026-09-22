<?php

namespace AKlump\DrupalConfigFixer\Tests\Unit\Helpers;

use AKlump\Drupal\ConfigFixer\Exception\DrupalFormatMismatchException;
use AKlump\Drupal\ConfigFixer\Helpers\VerifyDrupalFormat;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\Drupal\ConfigFixer\Helpers\VerifyDrupalFormat
 * @uses \AKlump\Drupal\ConfigFixer\Helpers\DumpYaml
 */
class VerifyDrupalFormatTest extends TestCase {

  public function dataForTestAcceptsDrupalExportsProvider() {
    $tests = [];
    // As exported by Drupal 11: a role, a mapping left empty, a literal block.
    $tests[] = ["uuid: abc\nlangcode: en\nstatus: true\ndependencies:\n  module:\n    - captcha\nid: editor\nlabel: Editor\nweight: 2\nis_admin: false\npermissions:\n  - 'access content overview'\n"];
    $tests[] = ["dependencies: {  }\nid: a\n"];
    $tests[] = ["a:\n  b:\n    c:\n      d:\n        e: 1\n"];
    $tests[] = ["body: |-\n  line one\n  line two\nid: a\n"];
    // An empty file: nothing to preserve.
    $tests[] = [''];

    return $tests;
  }

  /**
   * @dataProvider dataForTestAcceptsDrupalExportsProvider
   */
  public function testAcceptsDrupalExports(string $contents) {
    (new VerifyDrupalFormat())('foo.yml', $contents);
    $this->addToAssertionCount(1);
  }

  public function dataForTestRejectsOtherFormatsProvider() {
    $tests = [];
    // Inlined below four levels, as this library wrote before.
    $tests[] = ["a:\n  b:\n    c:\n      d: { e: 1 }\n", 4, "'      d: { e: 1 }'", "'      d:'"];
    // A comment.
    $tests[] = ["# local\nid: a\n", 1, "'# local'", "'id: a'"];
    // Four-space indent.
    $tests[] = ["a:\n    - b\n", 2, "'    - b'", "'  - b'"];
    // Missing final newline.
    $tests[] = ["id: a", 2, "(end of file)", "''"];

    return $tests;
  }

  /**
   * @dataProvider dataForTestRejectsOtherFormatsProvider
   */
  public function testRejectsOtherFormats(string $contents, int $line, string $exported, string $would_be) {
    try {
      (new VerifyDrupalFormat())('foo.yml', $contents);
      $this->fail('No exception was thrown.');
    }
    catch (DrupalFormatMismatchException $exception) {
      $message = $exception->getMessage();
      $this->assertStringStartsWith("foo.yml: re-saving this file unchanged would alter line $line", $message);
      $this->assertStringContainsString("exported: $exported", $message);
      $this->assertStringContainsString("would be: $would_be", $message);
    }
  }

  public function testReportsEndOfFile() {
    $this->expectException(DrupalFormatMismatchException::class);
    $this->expectExceptionMessage('would be: (end of file)');
    (new VerifyDrupalFormat())('foo.yml', "id: a\n\n");
  }

}
