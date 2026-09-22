<?php

namespace AKlump\DrupalConfigFixer\Tests\Unit;

use AKlump\Drupal\ConfigFixer\Files;
use AKlump\DrupalConfigFixer\Tests\TestTraits\TempConfigDirectoryTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\Drupal\ConfigFixer\Files
 * @uses \AKlump\Drupal\ConfigFixer\Traits\FileIOTrait
 */
class FilesTest extends TestCase {

  use TempConfigDirectoryTrait {
    tearDown as tempConfigDirectoryTearDown;
  }

  public function testConstructorSetsBasePath() {
    $this->assertSame('/foo', (new Files('/foo'))->getBasePath());
  }

  public function testDeleteRemovesExistingFile() {
    $path = $this->writeYaml('foo.yml', ['foo' => 'bar']);
    $files = new Files($this->getTempConfigDirectory());
    $this->assertSame($files, $files->delete('foo.yml'));
    $this->assertFileDoesNotExist($path);
  }

  public function testDeleteLeavesOtherFilesAlone() {
    $this->writeYaml('foo.yml', []);
    $other = $this->writeYaml('bar.yml', []);
    (new Files($this->getTempConfigDirectory()))->delete('foo.yml');
    $this->assertFileExists($other);
  }

  public function testDeleteMissingFileDoesNothing() {
    $files = new Files($this->getTempConfigDirectory());
    $this->assertSame($files, $files->delete('missing.yml'));
    $this->assertFileDoesNotExist($this->getTempConfigDirectory() . '/missing.yml');
  }

  public function testRestoreRecoversDeletedFileFromGit() {
    $base = $this->initGitRepository(['foo.yml' => 'foo: bar']);
    unlink("$base/foo.yml");

    $files = new Files($base);
    $this->assertSame($files, $files->restore('foo.yml'));
    $this->assertStringEqualsFile("$base/foo.yml", 'foo: bar');
  }

  public function testRestoreDiscardsUncommittedChanges() {
    $base = $this->initGitRepository(['foo.yml' => 'foo: bar']);
    file_put_contents("$base/foo.yml", 'foo: changed');
    (new Files($base))->restore('foo.yml');
    $this->assertStringEqualsFile("$base/foo.yml", 'foo: bar');
  }

  public function testRestoreAcceptsWildcardForDeletedFiles() {
    $base = $this->initGitRepository([
      'monolog.settings.yml' => 'a: 1',
      'monolog.channel.yml' => 'b: 2',
      'other.yml' => 'c: 3',
    ]);
    unlink("$base/monolog.settings.yml");
    unlink("$base/monolog.channel.yml");
    unlink("$base/other.yml");

    (new Files($base))->restore('monolog*.*');
    $this->assertFileExists("$base/monolog.settings.yml");
    $this->assertFileExists("$base/monolog.channel.yml");
    $this->assertFileDoesNotExist("$base/other.yml");
  }

  public function testRestoreWorksWhenBasePathContainsSpace() {
    $base = $this->initGitRepository(['foo.yml' => 'foo: bar'], 'has space');
    unlink("$base/foo.yml");
    (new Files($base))->restore('foo.yml');
    $this->assertStringEqualsFile("$base/foo.yml", 'foo: bar');
  }

  public function testRestoreAcceptsWildcardWhenBasePathContainsSpace() {
    $base = $this->initGitRepository([
      'monolog.settings.yml' => 'a: 1',
      'other.yml' => 'c: 3',
    ], 'has space');
    unlink("$base/monolog.settings.yml");
    unlink("$base/other.yml");
    (new Files($base))->restore('monolog*.*');
    $this->assertFileExists("$base/monolog.settings.yml");
    $this->assertFileDoesNotExist("$base/other.yml");
  }

  public function testRestoreDoesNotExecuteShellMetacharactersInBasePath() {
    $base = $this->initGitRepository(['foo.yml' => 'foo: bar'], 'a;touch pwned;b');
    unlink("$base/foo.yml");
    (new Files($base))->restore('foo.yml');
    $this->assertFileDoesNotExist("$base/pwned");
    $this->assertStringEqualsFile("$base/foo.yml", 'foo: bar');
  }

  public function testRestoreThrowsWhenGitFails() {
    $base = $this->initGitRepository(['foo.yml' => 'foo: bar']);
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('missing.yml');
    (new Files($base))->restore('missing.yml');
  }

  /**
   * @param array $files
   *   Keys are file names, values are the committed contents.
   * @param string $subdirectory
   *   Optional directory inside the temp directory to hold the repository.
   *
   * @return string
   *   The repository path.
   */
  private function initGitRepository(array $files, string $subdirectory = ''): string {
    exec('command -v git', $output, $exit_code);
    if ($exit_code !== 0) {
      $this->markTestSkipped('git is not available.');
    }
    $base = $this->getTempConfigDirectory();
    if ($subdirectory) {
      $base .= "/$subdirectory";
      mkdir($base);
    }
    foreach ($files as $name => $contents) {
      file_put_contents("$base/$name", $contents);
    }
    $git = 'git -C ' . escapeshellarg($base);
    exec("$git init -q && $git add -A && $git -c user.name=test -c user.email=test@example.com -c commit.gpgsign=false commit -q -m init", $output, $exit_code);
    $this->assertSame(0, $exit_code, 'Could not create the git fixture.');

    // Files::restore() runs git from the process's working directory.
    $this->originalWorkingDirectory = getcwd();
    chdir($base);

    return $base;
  }

  private $originalWorkingDirectory = '';

  protected function tearDown(): void {
    if ($this->originalWorkingDirectory) {
      chdir($this->originalWorkingDirectory);
      $this->originalWorkingDirectory = '';
    }
    $this->tempConfigDirectoryTearDown();
  }

}
