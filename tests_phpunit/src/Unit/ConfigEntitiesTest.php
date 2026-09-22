<?php

namespace AKlump\DrupalConfigFixer\Tests\Unit;

use AKlump\Drupal\ConfigFixer\ConfigEntities;
use AKlump\DrupalConfigFixer\Tests\TestTraits\TempConfigDirectoryTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\Drupal\ConfigFixer\ConfigEntities
 * @uses \AKlump\Drupal\ConfigFixer\Traits\FileIOTrait
 * @uses \AKlump\Drupal\ConfigFixer\Helpers\LoadFile
 * @uses \AKlump\Drupal\ConfigFixer\Helpers\SaveFile
 * @uses \AKlump\Drupal\ConfigFixer\Helpers\AddDependency
 * @uses \AKlump\Drupal\ConfigFixer\Helpers\RemoveDependency
 * @uses \AKlump\Drupal\ConfigFixer\Helpers\DumpYaml
 * @uses \AKlump\Drupal\ConfigFixer\Helpers\VerifyDrupalFormat
 * @uses \AKlump\Drupal\ConfigFixer\Helpers\VerifyOrder
 */
class ConfigEntitiesTest extends TestCase {

  use TempConfigDirectoryTrait;

  private function getConfig(array $files): ConfigEntities {
    foreach ($files as $name => $data) {
      $this->writeYaml("$name.yml", $data);
    }

    return new ConfigEntities($this->getTempConfigDirectory(), array_keys($files));
  }

  public function testAddDependencyWritesFileLikeDrupal() {
    $config = $this->getConfig([
      'block.block.captcha' => [
        'uuid' => 'abc',
        'langcode' => 'en',
        'status' => TRUE,
        'id' => 'captcha',
        'theme' => 'olivero',
      ],
    ]);
    $this->assertSame($config, $config->addDependency('captcha'));
    $this->assertSame(
      "uuid: abc\nlangcode: en\nstatus: true\ndependencies:\n  module:\n    - captcha\nid: captcha\ntheme: olivero\n",
      file_get_contents($this->getTempConfigDirectory() . '/block.block.captcha.yml')
    );
  }

  public function testAddDependencySortsNames() {
    $this->getConfig(['views.view.content' => ['dependencies' => ['module' => ['node', 'user']]]])
      ->addDependency('block');
    $this->assertSame(['block', 'node', 'user'], $this->readYaml('views.view.content.yml')['dependencies']['module']);
  }

  public function testAddDependencyAppliesToEveryFile() {
    $this->getConfig([
      'block.block.a' => ['id' => 'a'],
      'block.block.b' => ['dependencies' => ['module' => ['node']]],
    ])->addDependency('captcha');
    $this->assertSame(['captcha'], $this->readYaml('block.block.a.yml')['dependencies']['module']);
    $this->assertSame(['captcha', 'node'], $this->readYaml('block.block.b.yml')['dependencies']['module']);
  }

  public function testAddExistingDependencyDoesNotRewrite() {
    $this->getConfig(['block.block.a' => ['dependencies' => ['module' => ['captcha']]]]);
    $this->backdateFile('block.block.a.yml');
    (new ConfigEntities($this->getTempConfigDirectory(), ['block.block.a']))->addDependency('captcha');
    $this->assertFileNotRewritten('block.block.a.yml');
  }

  public function testNamesMayIncludeYmlExtension() {
    $this->getConfig(['block.block.a' => ['id' => 'a']]);
    (new ConfigEntities($this->getTempConfigDirectory(), ['block.block.a.yml']))->addDependency('captcha');
    $this->assertSame(['captcha'], $this->readYaml('block.block.a.yml')['dependencies']['module']);
  }

  public function testRemoveDependency() {
    $config = $this->getConfig(['block.block.a' => ['dependencies' => ['module' => ['captcha', 'node']]]]);
    $this->assertSame($config, $config->removeDependency('captcha'));
    $this->assertSame(['node'], $this->readYaml('block.block.a.yml')['dependencies']['module']);
  }

  public function testRemoveLastDependencyLeavesEmptyMapping() {
    $this->getConfig(['block.block.a' => ['dependencies' => ['module' => ['captcha']], 'id' => 'a']])
      ->removeDependency('captcha');
    $this->assertSame("dependencies: {  }\nid: a\n", file_get_contents($this->getTempConfigDirectory() . '/block.block.a.yml'));
  }

  public function testRemoveMissingDependencyDoesNotRewrite() {
    $this->getConfig(['block.block.a' => ['id' => 'a']]);
    $this->backdateFile('block.block.a.yml');
    (new ConfigEntities($this->getTempConfigDirectory(), ['block.block.a']))->removeDependency('captcha');
    $this->assertFileNotRewritten('block.block.a.yml');
  }

  public function testRemoveDependencyContinuesPastFileWithoutDependencies() {
    $this->getConfig([
      'block.block.a' => ['id' => 'a'],
      'block.block.b' => ['dependencies' => ['module' => ['captcha', 'node']]],
    ])->removeDependency('captcha');
    $this->assertSame(['node'], $this->readYaml('block.block.b.yml')['dependencies']['module']);
  }

  public function testMissingFileThrows() {
    $this->expectException(\Symfony\Component\Yaml\Exception\ParseException::class);
    (new ConfigEntities($this->getTempConfigDirectory(), ['block.block.missing']))->addDependency('captcha');
  }

  public function testAddDependencyOnEmptyFile() {
    file_put_contents($this->getTempConfigDirectory() . '/block.block.a.yml', '');
    (new ConfigEntities($this->getTempConfigDirectory(), ['block.block.a']))->addDependency('node');
    $this->assertSame(['dependencies' => ['module' => ['node']]], $this->readYaml('block.block.a.yml'));
  }

}
