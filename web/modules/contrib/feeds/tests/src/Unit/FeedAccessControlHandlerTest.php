<?php

namespace Drupal\Tests\feeds\Unit;

use Drupal\Core\Language\Language;
use Drupal\Core\Session\AccountInterface;
use Drupal\feeds\FeedAccessControlHandler;
use Drupal\feeds\FeedInterface;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\feeds\FeedAccessControlHandler
 * @group feeds
 */
class FeedAccessControlHandlerTest extends FeedsUnitTestCase {

  /**
   * Metadata class for the feed entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * The handler to test.
   *
   * @var \Drupal\feeds\FeedAccessControlHandler
   */
  protected $controller;

  /**
   * The Drupal module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->entityType = $this->createMock('\Drupal\Core\Entity\EntityTypeInterface');
    $this->entityType->expects($this->once())
      ->method('id')
      ->willReturn('feeds_feed');
    $this->controller = new FeedAccessControlHandler($this->entityType);
    $this->moduleHandler = $this->createMock('\Drupal\Core\Extension\ModuleHandlerInterface');
    $this->moduleHandler->expects($this->any())
      ->method('invokeAll')
      ->willReturn([]);
    $this->controller->setModuleHandler($this->moduleHandler);
  }

  /**
   * @covers ::access
   */
  public function testAccess() {
    $feed = $this->createMock('\Drupal\feeds\FeedInterface');
    $feed->expects($this->any())
      ->method('bundle')
      ->willReturn('feed_bundle');
    $feed->expects($this->any())
      ->method('language')
      ->willReturn(new Language());

    $account = $this->createMock('\Drupal\Core\Session\AccountInterface');

    $this->assertFalse($this->controller->access($feed, 'beep', $account));
    $this->assertFalse($this->controller->access($feed, 'unlock', $account));

    $this->controller->resetCache();

    $this->assertFalse($this->controller->access($feed, 'unlock', $account));

    $account->expects($this->any())
      ->method('hasPermission')
      ->with($this->equalTo('administer feeds'))
      ->willReturn(TRUE);

    $this->assertTrue($this->controller->access($feed, 'clear', $account));
    $this->assertTrue($this->controller->access($feed, 'view', $account));

    $account = $this->createMock('\Drupal\Core\Session\AccountInterface');

    $account->expects($this->exactly(2))
      ->method('hasPermission')
      ->with($this->logicalOr(
           $this->equalTo('administer feeds'),
           $this->equalTo('delete feed_bundle feeds')
       ))
      ->willReturn(FALSE, TRUE);
    $this->assertTrue($this->controller->access($feed, 'delete', $account));
  }

  /**
   * @covers ::createAccess
   */
  public function testCheckCreateAccess() {
    $account = $this->createMock('\Drupal\Core\Session\AccountInterface');

    $account->expects($this->exactly(2))
      ->method('hasPermission')
      ->with($this->logicalOr(
           $this->equalTo('administer feeds'),
           $this->equalTo('create feed_bundle feeds')
       ))
      ->willReturn(FALSE, FALSE);
    $this->assertFalse($this->controller->createAccess('feed_bundle', $account));

    $this->controller->resetCache();

    $account = $this->createMock('\Drupal\Core\Session\AccountInterface');
    $account->expects($this->exactly(2))
      ->method('hasPermission')
      ->with($this->logicalOr(
           $this->equalTo('administer feeds'),
           $this->equalTo('create feed_bundle feeds')
       ))
      ->willReturn(FALSE, TRUE);
    $this->assertTrue($this->controller->createAccess('feed_bundle', $account));
  }

  /**
   * Tests the checkAccess() method with the 'template' operation.
   *
   * @param bool $is_allowed
   *   If access is expected to be granted.
   * @param bool $is_owner
   *   Whether or not the account is owner of the feed.
   * @param array $permissions
   *   A list of permissions to that the user has.
   *
   * @covers ::checkAccess
   * @dataProvider providerTemplateAccess
   */
  public function testTemplateAccess(bool $is_allowed, bool $is_owner, array $permissions) {
    $method = $this->getMethod(FeedAccessControlHandler::class, 'checkAccess');

    $account = $this->prophesize(AccountInterface::class);
    $account->id()->willReturn(27);

    $feed_owner = $this->prophesize(AccountInterface::class);
    if ($is_owner) {
      $feed_owner->id()->willReturn(27);
    }
    else {
      $feed_owner->id()->willReturn(13);
    }

    $feed = $this->prophesize(FeedInterface::class);
    $feed->bundle()->willReturn('foo');
    $feed->getOwner()->willReturn($feed_owner->reveal());

    $account->hasPermission(Argument::type('string'))->will(function ($args) use ($permissions) {
      return in_array($args[0], $permissions);
    });

    $result = $method->invokeArgs($this->controller, [
      $feed->reveal(),
      'template',
      $account->reveal(),
    ]);

    if ($is_allowed) {
      $this->assertTrue($result->isAllowed());
    }
    else {
      $this->assertFalse($result->isAllowed());
    }
  }

  /**
   * Data provider for testTemplateAccess().
   *
   * @return array
   *   A list of test cases.
   */
  public static function providerTemplateAccess(): array {
    return [
      'administer feeds' => [
        'is_allowed' => TRUE,
        'is_owner' => FALSE,
        'permissions' => ['administer feeds'],
      ],
      'import foo feeds' => [
        'is_allowed' => TRUE,
        'is_owner' => FALSE,
        'permissions' => ['import foo feeds'],
      ],
      // If the user may only create feeds, but not update or import existing
      // (even if they are the owner) they may not access a feed specific
      // template, only the template for the feed type.
      'create foo feeds' => [
        'is_allowed' => FALSE,
        'is_owner' => TRUE,
        'permissions' => ['create foo feeds'],
      ],
      'update foo feeds' => [
        'is_allowed' => TRUE,
        'is_owner' => FALSE,
        'permissions' => ['update foo feeds'],
      ],
      'update own foo feeds' => [
        'is_allowed' => TRUE,
        'is_owner' => TRUE,
        'permissions' => ['update own foo feeds'],
      ],
      // If an user may only update their own feeds, they have no access to
      // templates of other feeds.
      'update other foo feeds' => [
        'is_allowed' => FALSE,
        'is_owner' => FALSE,
        'permissions' => ['update own foo feeds'],
      ],
      'import own foo feeds' => [
        'is_allowed' => TRUE,
        'is_owner' => TRUE,
        'permissions' => ['import own foo feeds'],
      ],
      'import other foo feeds' => [
        'is_allowed' => FALSE,
        'is_owner' => FALSE,
        'permissions' => ['import own foo feeds'],
      ],
      'schedule_import foo feeds' => [
        'is_allowed' => TRUE,
        'is_owner' => FALSE,
        'permissions' => ['schedule_import foo feeds'],
      ],
      'schedule_import own foo feeds' => [
        'is_allowed' => TRUE,
        'is_owner' => TRUE,
        'permissions' => ['schedule_import own foo feeds'],
      ],
      'schedule_import other foo feeds' => [
        'is_allowed' => FALSE,
        'is_owner' => FALSE,
        'permissions' => ['schedule_import own foo feeds'],
      ],
      'access content' => [
        'is_allowed' => FALSE,
        'is_owner' => TRUE,
        'permissions' => ['access content'],
      ],
    ];
  }

}
