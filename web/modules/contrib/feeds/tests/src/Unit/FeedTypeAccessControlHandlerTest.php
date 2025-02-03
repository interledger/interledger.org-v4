<?php

namespace Drupal\Tests\feeds\Unit;

use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\DependencyInjection\Container;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\feeds\FeedTypeAccessControlHandler;
use Drupal\feeds\FeedTypeInterface;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\feeds\FeedTypeAccessControlHandler
 * @group feeds
 */
class FeedTypeAccessControlHandlerTest extends FeedsUnitTestCase {

  /**
   * The entity to use with the access controller.
   *
   * @var \Prophecy\Prophecy\ProphecyInterface|\Drupal\feeds\FeedTypeInterface
   */
  protected $entity;

  /**
   * The account to use with the access controller.
   *
   * @var \Prophecy\Prophecy\ProphecyInterface|\Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The access controller handler to test.
   *
   * @var \Drupal\feeds\FeedTypeAccessControlHandler
   */
  protected $controller;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $cache_contexts_manager = $this->prophesize(CacheContextsManager::class);
    $cache_contexts_manager->assertValidTokens()->willReturn(TRUE);
    $cache_contexts_manager->reveal();
    $container = new Container();
    $container->set('cache_contexts_manager', $cache_contexts_manager);
    \Drupal::setContainer($container);

    $this->entity = $this->prophesize(FeedTypeInterface::class);
    $this->account = $this->prophesize(AccountInterface::class);

    $entity_type = $this->prophesize(EntityTypeInterface::class);
    $entity_type->getAdminPermission()->willReturn('administer feeds');
    $entity_type->id()->willReturn('foo');
    $this->controller = new FeedTypeAccessControlHandler($entity_type->reveal());
  }

  /**
   * @covers ::checkAccess
   */
  public function testCheckAccess() {
    $this->entity->id()->willReturn('foo');
    $this->account->hasPermission('view foo feeds')->willReturn(TRUE);

    $method = $this->getMethod(FeedTypeAccessControlHandler::class, 'checkAccess');
    $result = $method->invokeArgs($this->controller, [
      $this->entity->reveal(),
      'view',
      $this->account->reveal(),
    ]);
    $this->assertTrue($result->isAllowed());

    $this->entity->getCacheContexts()->willReturn([]);
    $this->entity->getCacheTags()->willReturn([]);
    $this->entity->getCacheMaxAge()->willReturn(0);

    $this->account->hasPermission('administer feeds')->willReturn(TRUE);
    $result = $method->invokeArgs($this->controller, [
      $this->entity->reveal(),
      'delete',
      $this->account->reveal(),
    ]);
    $this->assertTrue($result->isAllowed());

    $this->account->hasPermission('administer feeds')->willReturn(FALSE);
    $result = $method->invokeArgs($this->controller, [
      $this->entity->reveal(),
      'delete',
      $this->account->reveal(),
    ]);
    $this->assertFalse($result->isAllowed());

    $this->account->hasPermission('view foo feeds')->willReturn(FALSE);
    $result = $method->invokeArgs($this->controller, [
      $this->entity->reveal(),
      'view',
      $this->account->reveal(),
    ]);
    $this->assertFalse($result->isAllowed());

    $this->entity->isNew()->willReturn(TRUE);
    $result = $method->invokeArgs($this->controller, [
      $this->entity->reveal(),
      'delete',
      $this->account->reveal(),
    ]);
    $this->assertFalse($result->isAllowed());
  }

  /**
   * Tests the checkAccess() method with the 'template' operation.
   *
   * @param bool $is_allowed
   *   If access is expected to be granted.
   * @param array $permissions
   *   A list of permissions to that the user has.
   *
   * @covers ::checkAccess
   * @dataProvider providerTemplateAccess
   */
  public function testTemplateAccess(bool $is_allowed, array $permissions) {
    $method = $this->getMethod(FeedTypeAccessControlHandler::class, 'checkAccess');

    $this->account->hasPermission(Argument::type('string'))->will(function ($args) use ($permissions) {
      return in_array($args[0], $permissions);
    });
    $this->entity->id()->willReturn('foo');

    $result = $method->invokeArgs($this->controller, [
      $this->entity->reveal(),
      'template',
      $this->account->reveal(),
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
        'permissions' => ['administer feeds'],
      ],
      'import foo feeds' => [
        'is_allowed' => TRUE,
        'permissions' => ['import foo feeds'],
      ],
      'create foo feeds' => [
        'is_allowed' => TRUE,
        'permissions' => ['create foo feeds'],
      ],
      'update foo feeds' => [
        'is_allowed' => TRUE,
        'permissions' => ['update foo feeds'],
      ],
      'update own foo feeds' => [
        'is_allowed' => TRUE,
        'permissions' => ['update own foo feeds'],
      ],
      'import own foo feeds' => [
        'is_allowed' => TRUE,
        'permissions' => ['import own foo feeds'],
      ],
      'schedule_import foo feeds' => [
        'is_allowed' => TRUE,
        'permissions' => ['schedule_import foo feeds'],
      ],
      'schedule_import own foo feeds' => [
        'is_allowed' => TRUE,
        'permissions' => ['schedule_import own foo feeds'],
      ],
      'access content' => [
        'is_allowed' => FALSE,
        'permissions' => ['access content'],
      ],
    ];
  }

}
