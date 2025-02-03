<?php

namespace Drupal\Tests\feeds\Unit\Result;

use Drupal\Component\DependencyInjection\ReverseContainer;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Tests\feeds\Unit\FeedsUnitTestCase;
use Drupal\feeds\Result\HttpFetcherResult;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\feeds\Result\HttpFetcherResult
 * @group feeds
 */
class HttpFetcherResultTest extends FeedsUnitTestCase {

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Set a mock for the file_system service.
    $file_system = $this->prophesize(FileSystemInterface::class);
    $file_system->delete(Argument::any())->willReturn(TRUE);
    $container = new ContainerBuilder();
    $container->set(ReverseContainer::class, new ReverseContainer($container));
    $container->set('file_system', $file_system->reveal());
    \Drupal::setContainer($container);
  }

  /**
   * Tests unserializing.
   */
  public function testUnserialize() {
    $result = new HttpFetcherResult('public://foo', []);

    // Serialize and unserialize it again.
    $serialized = serialize($result);
    $unserialized = unserialize($serialized);

    // Call cleanUp(), which uses the file_system service.
    $unserialized->cleanUp();

    // And assert that the property $fileSystem is still set.
    $this->assertInstanceof(FileSystemInterface::class, $this->getProtectedProperty($unserialized, 'fileSystem'));
  }

  /**
   * Tests unserializing without a file_system service.
   *
   * It is possible that there exists a HttpFetcherResult object that was
   * serialized before the $file_system property was added to the class.
   */
  public function testUnserializeWithoutFileSystemService() {
    // Create an instance of HttpFetcherResult without calling the constructor.
    $class = new \ReflectionClass(HttpFetcherResult::class);
    $result = $class->newInstanceWithoutConstructor();
    // Set some properties.
    $this->setProtectedProperty($result, 'filePath', 'public://foo');
    $this->setProtectedProperty($result, 'headers', []);

    // Serialize and unserialize it again.
    $serialized = serialize($result);
    $unserialized = unserialize($serialized);

    // Call cleanUp(), which uses the file_system service.
    $unserialized->cleanUp();

    // And assert that the property $fileSystem is now set.
    $this->assertInstanceof(FileSystemInterface::class, $this->getProtectedProperty($unserialized, 'fileSystem'));
  }

}
