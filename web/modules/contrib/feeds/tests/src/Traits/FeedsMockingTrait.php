<?php

namespace Drupal\Tests\feeds\Traits;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\FeedTypeInterface;
use Drupal\feeds\Feeds\State\CleanState;
use Drupal\feeds\State;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;

/**
 * Provides methods for mocking certain Feeds classes.
 *
 * This trait is meant to be used only by test classes.
 */
trait FeedsMockingTrait {

  /**
   * Returns a mocked feed type entity.
   *
   * @return \Drupal\feeds\FeedTypeInterface
   *   A mocked feed type entity.
   */
  protected function getMockFeedType() {
    $feed_type = $this->createMock(FeedTypeInterface::class);
    $feed_type->id = 'test_feed_type';
    $feed_type->description = 'This is a test feed type';
    $feed_type->label = 'Test feed type';
    $feed_type->expects($this->any())
      ->method('label')
      ->willReturn($feed_type->label);

    return $feed_type;
  }

  /**
   * Returns a mocked feed entity.
   *
   * @return \Drupal\feeds\FeedInterface
   *   A mocked feed entity.
   */
  protected function getMockFeed() {
    $feed = $this->createMock(FeedInterface::class);
    $feed->expects($this->any())
      ->method('getType')
      ->willReturn($this->getMockFeedType());

    return $feed;
  }

  /**
   * Returns a mocked AccountSwitcher object.
   *
   * The returned object verifies that if switchTo() is called, switchBack()
   * is also called.
   *
   * @return \Drupal\Core\Session\AccountSwitcherInterface
   *   A mocked AccountSwitcher object.
   */
  protected function getMockedAccountSwitcher() {
    $switcher = $this->prophesize(AccountSwitcherInterface::class);

    $switcher->switchTo(Argument::type(AccountInterface::class))
      ->will(function () use ($switcher) {
        $switcher->switchBack()->shouldBeCalled();

        return $switcher->reveal();
      });

    return $switcher->reveal();
  }

  /**
   * Mocks an account object.
   *
   * @param array $perms
   *   The account's permissions.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The mocked account object.
   */
  protected function getMockAccount(array $perms = []) {
    $account = $this->createMock(AccountInterface::class);
    if ($perms) {
      $map = [];
      foreach ($perms as $perm => $has) {
        $map[] = [$perm, $has];
      }
      $account->expects($this->any())
        ->method('hasPermission')
        ->willReturnMap($map);
    }

    return $account;
  }

  /**
   * Mocks a field definition.
   *
   * @param array $settings
   *   The field storage and instance settings.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface
   *   A mocked field definition.
   */
  protected function getMockFieldDefinition(array $settings = []) {
    $definition = $this->createMock(FieldDefinitionInterface::class);
    $definition->expects($this->any())
      ->method('getSettings')
      ->willReturn($settings);

    return $definition;
  }

  /**
   * Mocks the file system.
   *
   * @return \Drupal\Core\File\FileSystemInterface
   *   A mocked file system.
   */
  protected function getMockFileSystem() {
    $definition = $this->createMock(FileSystemInterface::class);
    $definition->expects($this->any())
      ->method('tempnam')
      ->willReturnCallback(function () {
        $args = func_get_args();
        $dir = $args[1];
        mkdir('vfs://feeds/' . $dir);
        $file = 'vfs://feeds/' . $dir . '/' . mt_rand(10, 1000);
        touch($file);
        return $file;
      });
    return $definition;
  }

  /**
   * Returns a new State object.
   *
   * @return \Drupal\feeds\State
   *   A feed state object.
   */
  protected function createFeedsState(): State {
    return new State($this->createMock(MessengerInterface::class), $this->createMock(LoggerInterface::class));
  }

  /**
   * Returns a new CleanState object.
   *
   * @param int $feed_id
   *   The ID of the feed to create a clean state for.
   *
   * @return \Drupal\feeds\Feeds\State\CleanState
   *   A feed clean state object.
   */
  protected function createFeedsCleanState(int $feed_id): CleanState {
    $connection = $this->prophesize(Connection::class);
    $connection->query(Argument::type('string'), Argument::type('array'))->willReturn($this->createMock(StatementInterface::class));

    return new CleanState($feed_id, $this->createMock(MessengerInterface::class), $this->createMock(LoggerInterface::class), $connection->reveal(), $this->createMock(EntityTypeManagerInterface::class));
  }

}
