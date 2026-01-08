<?php

namespace Drupal\Tests\feeds_tamper\Functional;

/**
 * Tests that the Tamper link is shown on the feed type list page.
 *
 * @group feeds_tamper
 */
class FeedTypeListBuilderTest extends FeedsTamperBrowserTestBase {

  /**
   * Tests that the tamper operation is displayed on the feed type list page.
   */
  public function testUiWithRestrictedPrivileges() {
    // Add two feed types.
    $this->createFeedType([
      'id' => 'my_feed_type',
      'label' => 'My feed type',
    ]);
    $this->createFeedType([
      'id' => 'my_feed_type_restricted',
      'label' => 'My feed type (restricted)',
    ]);

    // Add a user who may only tamper 'my_feed_type'.
    $account = $this->drupalCreateUser([
      'administer feeds',
      'tamper my_feed_type',
    ]);
    $this->drupalLogin($account);

    // Assert that the tamper operation links is being displayed only for
    // my_feed_type .
    $this->drupalGet('/admin/structure/feeds');
    $session = $this->assertSession();

    $session->linkExists('Tamper');
    $session->linkByHrefExists('/admin/structure/feeds/manage/my_feed_type/tamper');
    $session->linkByHrefNotExists('/admin/structure/feeds/manage/my_feed_type_restricted/tamper');
  }

  /**
   * Tests that the weight range selection increases when having many tampers.
   *
   * By default, the weight range for a tamper plugin is from -10 to 10. So
   * that's room for 21 tamper plugin instances. But when there are more than 21
   * tampers, the weight range should become bigger.
   */
  public function testDeltaIncreaseWithManyTampers() {
    $feed_type_tamper_manager = $this->container->get('feeds_tamper.feed_type_tamper_manager');

    $feed_type = $this->createFeedType([
      'id' => 'my_feed_type',
      'label' => 'My feed type',
    ]);

    // Add a tamper.
    $uuid = $feed_type_tamper_manager->getTamperMeta($feed_type)
      ->addTamper([
        'plugin' => 'convert_case',
        'operation' => 'strtoupper',
        'source' => 'title',
        'description' => 'Convert the case to uppercase.',
      ]);
    $feed_type->save();

    // Assert that weight selector ranges from -10 to 10.
    $this->drupalGet('/admin/structure/feeds/manage/my_feed_type/tamper');
    $weights = $this->findWeightRange($uuid);
    $this->assertEquals(range(-10, 10), $weights);

    // Now add 19 more tampers and assert that the weight selector still ranges
    // from -10 to 10.
    for ($i = 0; $i < 19; $i++) {
      $feed_type_tamper_manager->getTamperMeta($feed_type)
        ->addTamper([
          'plugin' => 'convert_case',
          'operation' => 'strtoupper',
          'source' => 'title',
          'description' => 'Convert the case to uppercase.',
        ]);
    }
    $feed_type->save();

    $this->drupalGet('/admin/structure/feeds/manage/my_feed_type/tamper');
    $weights = $this->findWeightRange($uuid);
    $this->assertEquals(range(-10, 10), $weights);

    // Finally, add two more tampers. Assert that weight selector now ranges
    // from -11 to 11.
    for ($i = 0; $i < 2; $i++) {
      $feed_type_tamper_manager->getTamperMeta($feed_type)
        ->addTamper([
          'plugin' => 'convert_case',
          'operation' => 'strtoupper',
          'source' => 'title',
          'description' => 'Convert the case to uppercase.',
        ]);
    }
    $feed_type->save();

    $this->drupalGet('/admin/structure/feeds/manage/my_feed_type/tamper');
    $weights = $this->findWeightRange($uuid);
    $this->assertEquals(range(-11, 11), $weights);
  }

  /**
   * Tries to find the weight options for the given Tamper plugin instance.
   *
   * @param string $uuid
   *   The UUID of the Tamper plugin instance.
   *
   * @return string[]
   *   A list of weight options.
   */
  protected function findWeightRange(string $uuid): array {
    $options = $this->getSession()
      ->getPage()
      ->findAll('xpath', "//select[@id='edit-title-$uuid-weight']/option/@value");

    $weights = [];
    foreach ($options as $option) {
      $weights[] = $option->getText();
    }

    return $weights;
  }

}
