<?php

namespace Drupal\Tests\feeds\FunctionalJavascript;

use Drupal\feeds\Entity\FeedType;

/**
 * @coversDefaultClass \Drupal\feeds\FeedTypeForm
 * @group feeds
 *
 * @todo without "administer users" permission a feed type cannot be created in
 * this test.
 * @todo test fails when the "file" module is not enabled.
 */
class FeedTypeFormTest extends FeedsJavascriptTestBase {

  /**
   * Tests selecting the directory fetcher.
   *
   * When switching fetchers, the part where you can configure the fetcher
   * should get updated on the form.
   */
  public function testSetDirectoryFetcher() {
    $this->drupalGet('/admin/structure/feeds/add');

    $session = $this->getSession();
    $assert_session = $this->assertSession();
    $page = $session->getPage();

    $assert_session->fieldExists('fetcher');
    $page->selectFieldOption('fetcher', 'directory');
    $assert_session->assertWaitOnAjaxRequest();

    // Assert that the form is updated.
    $assert_session->fieldExists('fetcher_configuration[allowed_extensions]');
    $assert_session->fieldExists('fetcher_configuration[allowed_schemes][public]');

    // Fill in the required fields on the form.
    $page->findField('label')->setValue('Foo');

    // Wait for machine name button to become visible.
    $assert_session->waitForText('Machine name');

    // And submit the form.
    $this->submitForm([], 'Save and add mappings');

    // Load the feed type and check the selected fetcher.
    $feed_type = FeedType::load('foo');
    $this->assertEquals('directory', $feed_type->get('fetcher'));
  }

  /**
   * Tests selecting the upload fetcher.
   *
   * When switching fetchers, the part where you can configure the fetcher
   * should get updated on the form.
   */
  public function testSetUploadFetcher() {
    $this->drupalGet('/admin/structure/feeds/add');

    $session = $this->getSession();
    $assert_session = $this->assertSession();
    $page = $session->getPage();

    // Fill in the required fields on the form.
    $page->findField('label')->setValue('Foo');

    // Wait for machine name button to become visible.
    $assert_session->waitForText('Machine name');

    $assert_session->fieldExists('fetcher');
    $page->selectFieldOption('fetcher', 'upload');
    $assert_session->assertWaitOnAjaxRequest();

    // Assert that the form is updated.
    $assert_session->fieldExists('fetcher_configuration[allowed_extensions]');
    $assert_session->fieldExists('fetcher_configuration[directory]');

    // And submit the form.
    $this->submitForm([], 'Save and add mappings');

    // Load the feed type and check the selected fetcher.
    $feed_type = FeedType::load('foo');
    $this->assertEquals('upload', $feed_type->get('fetcher'));
  }

}
