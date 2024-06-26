<?php

namespace Drupal\Tests\feeds_ex\Unit;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\State\StateInterface;
use Drupal\feeds_ex\JmesRuntimeFactory;
use org\bovigo\vfs\vfsStream;

/**
 * @coversDefaultClass \Drupal\feeds_ex\JmesRuntimeFactory
 * @group feeds_ex
 */
class JmesRuntimeFactoryTest extends UnitTestBase {

  /**
   * A factory to generate JMESPath runtime objects.
   *
   * @var \Drupal\feeds_ex\JmesRuntimeFactory
   */
  protected $factory;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $file_system = $this->createMock(FileSystemInterface::class);
    $state = $this->createMock(StateInterface::class);
    $this->factory = new JmesRuntimeFactory($file_system, $state);
  }

  /**
   * @covers ::createRuntime
   */
  public function testCreateRuntime() {
    $runtime = $this->factory->createRuntime();
    $this->assertTrue(method_exists($runtime, '__invoke'));
  }

  /**
   * @covers ::createAstRuntime
   */
  public function testCreateAstRuntime() {
    $runtime = $this->factory->createAstRuntime();
    $this->assertInstanceOf('JmesPath\AstRuntime', $runtime);
  }

  /**
   * @covers ::createCompilerRuntime
   */
  public function testCreateCompilerRuntime() {
    $stream = vfsStream::setup('feeds');
    $runtime = $this->factory->createCompilerRuntime($stream->url());
    $this->assertInstanceOf('JmesPath\CompilerRuntime', $runtime);
  }

}
