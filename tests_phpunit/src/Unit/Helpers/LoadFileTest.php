<?php

namespace AKlump\DrupalConfigFixer\Tests\Unit\Helpers;

use AKlump\Drupal\ConfigFixer\Helpers\LoadFile;
use AKlump\DrupalConfigFixer\Tests\TestTraits\TempConfigDirectoryTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * @covers \AKlump\Drupal\ConfigFixer\Helpers\LoadFile
 */
class LoadFileTest extends TestCase {

  use TempConfigDirectoryTrait;

  public function testInvokeParsesYamlFile() {
    $path = $this->getTempConfigDirectory() . '/foo.yml';
    file_put_contents($path, "module:\n  foo: 0\npermissions:\n  - 'do stuff'\n");
    $this->assertSame([
      'module' => ['foo' => 0],
      'permissions' => ['do stuff'],
    ], (new LoadFile())($path));
  }

  public function testInvokeReturnsEmptyArrayForEmptyFile() {
    $path = $this->getTempConfigDirectory() . '/empty.yml';
    file_put_contents($path, '');
    $this->assertSame([], (new LoadFile())($path));
  }

  public function testInvokeThrowsOnMissingFile() {
    $this->expectException(ParseException::class);
    (new LoadFile())($this->getTempConfigDirectory() . '/missing.yml');
  }

}
