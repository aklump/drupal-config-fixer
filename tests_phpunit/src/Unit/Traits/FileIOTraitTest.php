<?php

namespace AKlump\DrupalConfigFixer\Tests\Unit\Traits;

use AKlump\Drupal\ConfigFixer\Traits\FileIOTrait;
use AKlump\DrupalConfigFixer\Tests\TestTraits\TempConfigDirectoryTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\Drupal\ConfigFixer\Traits\FileIOTrait
 * @uses \AKlump\Drupal\ConfigFixer\Helpers\LoadFile
 * @uses \AKlump\Drupal\ConfigFixer\Helpers\SaveFile
 * @uses \AKlump\Drupal\ConfigFixer\Helpers\DumpYaml
 * @uses \AKlump\Drupal\ConfigFixer\Helpers\VerifyDrupalFormat
 * @uses \AKlump\Drupal\ConfigFixer\Helpers\VerifyOrder
 */
class FileIOTraitTest extends TestCase {

  use TempConfigDirectoryTrait;

  private function getObject() {
    return new class {

      use FileIOTrait;

    };
  }

  public function testSetBasePathReturnsSelf() {
    $obj = $this->getObject();
    $this->assertSame($obj, $obj->setBasePath('/foo'));
    $this->assertSame('/foo', $obj->getBasePath());
  }

  public function testLoadReadsRelativeToBasePath() {
    $this->writeYaml('foo.yml', ['foo' => 'bar']);
    $obj = $this->getObject()->setBasePath($this->getTempConfigDirectory());
    $this->assertSame(['foo' => 'bar'], $obj->load('foo.yml'));
  }

  public function testSaveWritesRelativeToBasePath() {
    $obj = $this->getObject()->setBasePath($this->getTempConfigDirectory());
    $obj->save('foo.yml', ['foo' => ['bar', 'baz']]);
    $this->assertSame(['foo' => ['bar', 'baz']], $this->readYaml('foo.yml'));
  }

}
