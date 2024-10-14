<?php

namespace Drupal\Tests\feeds_tamper\Functional;

/**
 * Tests access for the Tamper pages.
 *
 * @group feeds_tamper
 */
class AccessTest extends FeedsTamperBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'feeds',
    'feeds_tamper',
    'node',
    'user',
    'block',
  ];

  /**
   * The manager for FeedTypeTamperMeta instances.
   *
   * @var \Drupal\feeds_tamper\FeedTypeTamperManager
   */
  protected $feedTypeTamperManager;

  /**
   * The UUID of the first tamper plugin instance.
   *
   * @var string
   */
  protected $tamper1uuid;

  /**
   * The UUID of the second tamper plugin instance.
   *
   * @var string
   */
  protected $tamper2uuid;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Add two feed types.
    $feed_type1 = $this->createFeedType([
      'id' => 'my_feed_type',
      'label' => 'My feed type',
    ]);
    $feed_type2 = $this->createFeedType([
      'id' => 'my_feed_type_restricted',
      'label' => 'My feed type (restricted)',
    ]);

    // Display tabs.
    $this->drupalPlaceBlock('local_tasks_block');

    // Get the manager for FeedTypeTamperMeta instances.
    $this->feedTypeTamperManager = $this->container->get('feeds_tamper.feed_type_tamper_manager');

    // Programmatically add a tamper plugin for both feed types.
    $tamper_meta = $this->feedTypeTamperManager->getTamperMeta($feed_type1, TRUE);
    $this->tamper1uuid = $tamper_meta->addTamper([
      'plugin' => 'trim',
      'label' => 'Trim Title',
      'side' => 'trim',
      'source' => 'title',
    ]);
    $feed_type1->save();

    $tamper_meta = $this->feedTypeTamperManager->getTamperMeta($feed_type2, TRUE);
    $this->tamper2uuid = $tamper_meta->addTamper([
      'plugin' => 'trim',
      'label' => 'Trim Title',
      'side' => 'trim',
      'source' => 'title',
    ]);
    $feed_type2->save();
  }

  /**
   * Tests that an admin has access to all Tamper pages.
   */
  public function testAsAdmin() {
    // Go the edit page of the first feed type.
    $this->drupalGet('/admin/structure/feeds/manage/my_feed_type');
    $session = $this->assertSession();
    $session->statusCodeEquals(200);
    $session->linkExists('Tamper');
    $session->linkByHrefExists('/admin/structure/feeds/manage/my_feed_type/tamper');

    // Now go the Tamper page of the first feed type.
    $this->drupalGet('/admin/structure/feeds/manage/my_feed_type/tamper');
    $session->statusCodeEquals(200);

    // Check if the add tamper page is accessible.
    $this->drupalGet('/admin/structure/feeds/manage/my_feed_type/tamper/add/guid');
    $session->statusCodeEquals(200);

    // Check if the page to edit a tamper is accessible.
    $this->drupalGet('/admin/structure/feeds/manage/my_feed_type/tamper/' . $this->tamper1uuid . '/edit');
    $session->statusCodeEquals(200);

    // Check if the page to delete a tamper is accessible.
    $this->drupalGet('/admin/structure/feeds/manage/my_feed_type/tamper/' . $this->tamper1uuid . '/delete');
    $session->statusCodeEquals(200);
  }

  /**
   * Tests that a feed tamper admin has access to all Tamper pages.
   */
  public function testAsFeedTamperAdmin() {
    // Create a user that may only administer tampers.
    $tamperAdmin = $this->drupalCreateUser([
      'administer feeds_tamper',
    ]);
    $this->drupalLogin($tamperAdmin);

    // The user may not access the feed type edit page, but it may access the
    // tamper page.
    $this->drupalGet('/admin/structure/feeds/manage/my_feed_type');
    $session = $this->assertSession();
    $session->statusCodeEquals(403);
    $this->drupalGet('/admin/structure/feeds/manage/my_feed_type/tamper');
    $session->statusCodeEquals(200);

    // Check if the add tamper page is accessible.
    $this->drupalGet('/admin/structure/feeds/manage/my_feed_type/tamper/add/guid');
    $session->statusCodeEquals(200);

    // Check if the page to edit a tamper is accessible.
    $this->drupalGet('/admin/structure/feeds/manage/my_feed_type/tamper/' . $this->tamper1uuid . '/edit');
    $session->statusCodeEquals(200);

    // Check if the page to delete a tamper is accessible.
    $this->drupalGet('/admin/structure/feeds/manage/my_feed_type/tamper/' . $this->tamper1uuid . '/delete');
    $session->statusCodeEquals(200);
  }

  /**
   * Tests with limited privileges.
   *
   * A user that may only configure tampers for a specific feed type should be
   * able to access only tamper pages for that feed type and not for other feed
   * types.
   */
  public function testWithLimitedPrivileges() {
    // Add a user who may only tamper 'my_feed_type'.
    $account = $this->drupalCreateUser([
      'administer feeds',
      'tamper my_feed_type',
    ]);
    $this->drupalLogin($account);

    // Go the edit page of the first feed type.
    $this->drupalGet('/admin/structure/feeds/manage/my_feed_type');
    $session = $this->assertSession();
    $session->statusCodeEquals(200);
    $session->linkExists('Tamper');
    $session->linkByHrefExists('/admin/structure/feeds/manage/my_feed_type/tamper');

    // Now go the Tamper page of the first feed type.
    $this->drupalGet('/admin/structure/feeds/manage/my_feed_type/tamper');
    $session->statusCodeEquals(200);

    // Check if the add tamper page is accessible.
    $this->drupalGet('/admin/structure/feeds/manage/my_feed_type/tamper/add/guid');
    $session->statusCodeEquals(200);

    // Check if the page to edit a tamper is accessible.
    $this->drupalGet('/admin/structure/feeds/manage/my_feed_type/tamper/' . $this->tamper1uuid . '/edit');
    $session->statusCodeEquals(200);

    // Check if the page to delete a tamper is accessible.
    $this->drupalGet('/admin/structure/feeds/manage/my_feed_type/tamper/' . $this->tamper1uuid . '/delete');
    $session->statusCodeEquals(200);

    // Check that the user does not see the Tamper tab for the second feed type.
    $this->drupalGet('/admin/structure/feeds/manage/my_feed_type_restricted');
    $session = $this->assertSession();
    $session->statusCodeEquals(200);
    $session->linkNotExists('Tamper');
    $session->linkByHrefNotExists('/admin/structure/feeds/manage/my_feed_type_restricted/tamper');

    // Check that the user may not access the Tamper page of the second feed
    // type.
    $this->drupalGet('/admin/structure/feeds/manage/my_feed_type_restricted/tamper');
    $session->statusCodeEquals(403);

    // Check that the user may not add a tamper for the second feed type.
    $this->drupalGet('/admin/structure/feeds/manage/my_feed_type_restricted/tamper/add/guid');
    $session->statusCodeEquals(403);

    // Check that the user may not edit a tamper for the second feed type.
    $this->drupalGet('/admin/structure/feeds/manage/my_feed_type_restricted/tamper/' . $this->tamper2uuid . '/edit');
    $session->statusCodeEquals(403);

    // Check that the user may not delete a tamper for the second feed type.
    $this->drupalGet('/admin/structure/feeds/manage/my_feed_type_restricted/tamper/' . $this->tamper2uuid . '/delete');
    $session->statusCodeEquals(403);
  }

}
