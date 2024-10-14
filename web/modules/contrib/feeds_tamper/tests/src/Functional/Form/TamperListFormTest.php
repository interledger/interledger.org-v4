<?php

namespace Drupal\Tests\feeds_tamper\Functional\Form;

use Drupal\feeds\FeedTypeInterface;
use Drupal\Tests\feeds_tamper\Functional\FeedsTamperBrowserTestBase;

/**
 * Tests the page that lists all Tamper plugins.
 *
 * @group feeds_tamper
 */
class TamperListFormTest extends FeedsTamperBrowserTestBase {

  /**
   * The manager for FeedTypeTamperMeta instances.
   *
   * @var \Drupal\feeds_tamper\FeedTypeTamperManager
   */
  protected $feedTypeTamperManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->feedTypeTamperManager = $this->container->get('feeds_tamper.feed_type_tamper_manager');
  }

  /**
   * Creates a feed type with no mappings.
   *
   * @return \Drupal\feeds\FeedTypeInterface
   *   The created feed type.
   */
  protected function createFeedTypeWithNoMapping(): FeedTypeInterface {
    return $this->createFeedType([
      'id' => 'my_feed_type',
      'label' => 'My feed type',
      'mappings' => [],
    ]);
  }

  /**
   * Creates a feed type with mapping to the body field.
   *
   * @return \Drupal\feeds\FeedTypeInterface
   *   The created feed type.
   */
  protected function createFeedTypeWithBodyMapping(): FeedTypeInterface {
    // Add body field.
    node_add_body_field($this->nodeType);

    return $this->createFeedType([
      'id' => 'my_feed_type',
      'label' => 'My feed type',
      'mappings' => array_merge($this->getDefaultMappings(), [
        [
          'target' => 'body',
          'map' => [
            'summary' => 'description',
            'value' => 'content',
          ],
        ],
      ]),
    ]);
  }

  /**
   * Tests the page with zero mappings.
   */
  public function testPageWithZeroMappings() {
    $this->createFeedTypeWithNoMapping();

    // Go to the tamper listing.
    $this->drupalGet('/admin/structure/feeds/manage/my_feed_type/tamper');
    $this->assertSession()->pageTextContains('There are no mappings defined for this feed type.');
  }

  /**
   * Tests the page with zero tampers.
   */
  public function testPageWithZeroTampers() {
    $this->createFeedTypeWithBodyMapping();

    // Go to the tamper listing.
    $this->drupalGet('/admin/structure/feeds/manage/my_feed_type/tamper');

    $session = $this->assertSession();

    // Check if all targets are displayed.
    $session->pageTextContains('Item GUID -> Feeds item: guid');
    $session->pageTextContains('Title -> Title: value');
    $session->pageTextContains('Description -> Body: Summary');
    $session->pageTextContains('Content -> Body: Text');

    // Check that there are links displayed for each target.
    $session->linkByHrefExists('/admin/structure/feeds/manage/my_feed_type/tamper/add/guid');
    $session->linkByHrefExists('/admin/structure/feeds/manage/my_feed_type/tamper/add/title');
    $session->linkByHrefExists('/admin/structure/feeds/manage/my_feed_type/tamper/add/description');
    $session->linkByHrefExists('/admin/structure/feeds/manage/my_feed_type/tamper/add/content');
  }

  /**
   * Tests the page with a few tampers created.
   */
  public function testPageWithTampers() {
    $feed_type = $this->createFeedTypeWithBodyMapping();

    // Programmatically add a few tamper plugin instances.
    $tamper_meta = $this->feedTypeTamperManager->getTamperMeta($feed_type, TRUE);
    $uuid_content_1 = $tamper_meta->addTamper([
      'plugin' => 'explode',
      'label' => 'Explode',
      'separator' => '|',
      'source' => 'content',
    ]);
    $uuid_content_2 = $tamper_meta->addTamper([
      'plugin' => 'implode',
      'label' => 'Implode',
      'glue' => '-',
      'source' => 'content',
    ]);
    $uuid_content_3 = $tamper_meta->addTamper([
      'plugin' => 'trim',
      'label' => 'Trim Content',
      'side' => 'trim',
      'source' => 'content',
    ]);
    $uuid_title_1 = $tamper_meta->addTamper([
      'plugin' => 'trim',
      'label' => 'Trim Title',
      'side' => 'trim',
      'source' => 'title',
    ]);
    $uuid_title_2 = $tamper_meta->addTamper([
      'plugin' => 'required',
      'label' => 'Required',
      'invert' => FALSE,
      'source' => 'title',
    ]);
    $feed_type->save();

    // Go to the tamper listing.
    $this->drupalGet('/admin/structure/feeds/manage/my_feed_type/tamper');

    $title_tampers = [
      'labels' => [
        'Required',
        'Trim Title',
      ],
      'links' => [
        '/admin/structure/feeds/manage/my_feed_type/tamper/' . $uuid_title_1 . '/edit',
        '/admin/structure/feeds/manage/my_feed_type/tamper/' . $uuid_title_1 . '/delete',
        '/admin/structure/feeds/manage/my_feed_type/tamper/' . $uuid_title_2 . '/edit',
        '/admin/structure/feeds/manage/my_feed_type/tamper/' . $uuid_title_2 . '/delete',
      ],
    ];

    $content_tampers = [
      'labels' => [
        'Explode',
        'Implode',
        'Trim Content',
      ],
      'links' => [
        '/admin/structure/feeds/manage/my_feed_type/tamper/' . $uuid_content_1 . '/edit',
        '/admin/structure/feeds/manage/my_feed_type/tamper/' . $uuid_content_1 . '/delete',
        '/admin/structure/feeds/manage/my_feed_type/tamper/' . $uuid_content_2 . '/edit',
        '/admin/structure/feeds/manage/my_feed_type/tamper/' . $uuid_content_2 . '/delete',
        '/admin/structure/feeds/manage/my_feed_type/tamper/' . $uuid_content_3 . '/edit',
        '/admin/structure/feeds/manage/my_feed_type/tamper/' . $uuid_content_3 . '/delete',
      ],
    ];

    $link_modify_labels = [
      'Edit',
      'Delete',
    ];

    // Assert that for "title" two tampers are displayed and that these have
    // edit and delete links.
    $this->assertTextsDisplayedWithin(array_merge($title_tampers['labels'], $link_modify_labels), '#edit-title');
    $this->assertLinksDisplayedWithin($title_tampers['links'], '#edit-title');
    $this->assertTextsNotDisplayedWithin($content_tampers['labels'], '#edit-title');
    $this->assertLinksNotDisplayedWithin($content_tampers['links'], '#edit-title');

    // Now check the listing for "content".
    $this->assertTextsDisplayedWithin(array_merge($content_tampers['labels'], $link_modify_labels), '#edit-content');
    $this->assertLinksDisplayedWithin($content_tampers['links'], '#edit-content');
    $this->assertTextsNotDisplayedWithin($title_tampers['labels'], '#edit-content');
    $this->assertLinksNotDisplayedWithin($title_tampers['links'], '#edit-content');

    // Check that the other two have no tampers at all.
    $all_labels = array_merge($title_tampers['labels'], $content_tampers['labels'], $link_modify_labels);
    $all_links = array_merge($title_tampers['links'], $content_tampers['links']);

    $this->assertTextsNotDisplayedWithin($all_labels, '#edit-guid');
    $this->assertLinksNotDisplayedWithin($all_links, '#edit-guid');
    $this->assertTextsNotDisplayedWithin($all_labels, '#edit-description');
    $this->assertLinksNotDisplayedWithin($all_links, '#edit-description');
  }

  /**
   * Asserts displayed text within the specified section.
   *
   * @param string[] $texts
   *   The texts to look for.
   * @param string $css_id
   *   The CSS locator to look within.
   */
  protected function assertTextsDisplayedWithin(array $texts, string $css_id): void {
    $section_text = $this->getSession()
      ->getPage()
      ->find('css', $css_id)
      ->getText();

    foreach ($texts as $text) {
      $this->assertStringContainsString($text, $section_text);
    }
  }

  /**
   * Asserts that the text is NOT displayed within the specified section.
   *
   * @param string[] $texts
   *   The texts to look for.
   * @param string $css_id
   *   The CSS locator to look within.
   */
  protected function assertTextsNotDisplayedWithin(array $texts, string $css_id): void {
    $section_text = $this->getSession()
      ->getPage()
      ->find('css', $css_id)
      ->getText();

    foreach ($texts as $text) {
      $this->assertStringNotContainsString($text, $section_text);
    }
  }

  /**
   * Assert that the links are displayed within the specified section.
   *
   * @param string[] $links
   *   The links to look for.
   * @param string $css_id
   *   The CSS locator to look within.
   */
  protected function assertLinksDisplayedWithin(array $links, string $css_id): void {
    $section = $this->getSession()
      ->getPage()
      ->find('css', $css_id);

    $session = $this->assertSession();

    foreach ($links as $link) {
      $xpath = $session->buildXPathQuery('//a[contains(@href, :href)]', [':href' => $link]);
      $message = strtr('No link containing href %href found.', ['%href' => $link]);

      $found_links = $section->findAll('xpath', $xpath);
      $this->assertTrue(!empty($found_links[0]), $message);
    }
  }

  /**
   * Assert that the links are NOT displayed within the specified section.
   *
   * @param string[] $links
   *   The links to look for.
   * @param string $css_id
   *   The CSS locator to look within.
   */
  protected function assertLinksNotDisplayedWithin(array $links, string $css_id): void {
    $section = $this->getSession()
      ->getPage()
      ->find('css', $css_id);

    $session = $this->assertSession();

    foreach ($links as $link) {
      $xpath = $session->buildXPathQuery('//a[contains(@href, :href)]', [':href' => $link]);
      $message = strtr('Link containing href %href found.', ['%href' => $link]);
      $found_links = $section->findAll('xpath', $xpath);
      $this->assertTrue(empty($found_links), $message);
    }
  }

}
