<?php

namespace Drupal\Tests\svg_image_field\Functional;

use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\svg_image_field\Traits\SvgImageFieldCreationTrait;

/**
 * Provides a base class for SVG Image Field functional tests.
 */
abstract class SvgImageFieldBrowserTestBase extends BrowserTestBase {

  use SvgImageFieldCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'svg_image_field',
    'node',
    'field_ui',
  ];

  /**
   * A user with permissions to administer content types and image styles.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * The node type to test with.
   *
   * @var \Drupal\node\Entity\NodeType
   */
  protected $nodeType;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Create a content type.
    $this->nodeType = NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ]);
    $this->nodeType->save();

    $this->adminUser = $this->drupalCreateUser([
      'access content',
      'access administration pages',
      'administer site configuration',
      'administer content types',
      'administer node fields',
      'administer nodes',
      'create article content',
      'edit any article content',
      'delete any article content',
      'administer node display',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Uploads an image to a node.
   *
   * @param string $image_uri
   *   The uri of the image to upload.
   * @param string $field_name
   *   Name of the image field the image should be attached to.
   * @param string $type
   *   The type of node to create.
   * @param string $alt
   *   The alt text for the image. Use if the field settings require alt text.
   */
  public function uploadNodeImage(string $image_uri, string $field_name, string $type, string $alt = '') {
    $edit = [
      'title[0][value]' => 'Foo',
    ];
    $edit['files[' . $field_name . '_0]'] = $image_uri;
    $this->drupalGet('node/add/' . $type);
    $this->submitForm($edit, 'Save');
    if ($alt) {
      // Add alt text.
      $this->submitForm([$field_name . '[0][alt]' => $alt], 'Save');
    }

    // Retrieve ID of the newly created node from the current URL.
    $matches = [];
    preg_match('/node\/([0-9]+)/', $this->getUrl(), $matches);
    return $matches[1] ?? FALSE;
  }

}
