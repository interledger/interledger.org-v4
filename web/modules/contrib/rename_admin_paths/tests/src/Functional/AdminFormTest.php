<?php

declare(strict_types=1);

namespace Drupal\Tests\rename_admin_paths\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests to ensure that the admin form works correctly.
 *
 * @group rename_admin_paths
 */
class AdminFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['rename_admin_paths'];

  /**
   * Setup admin user.
   */
  protected function setUp(): void {
    parent::setUp();

    $admin = $this->drupalCreateUser(['administer modules', 'administer site configuration'], 'administrator', TRUE);
    $this->drupalLogin($admin);
  }

  /**
   * Test admin is able to view the settings form.
   */
  public function testViewForm(): void {
    $this->drupalGet('admin/config/system/rename-admin-paths');

    $this->assertRenameAdminPathFormIsVisible();

    $this->assertSession()->fieldValueEquals('admin_path_value', 'backend');
    $this->assertSession()->fieldValueEquals('user_path_value', 'member');
  }

  /**
   * Test /admin + /user paths removed when changed to /backend + /member.
   */
  public function testEnablePathReplacements(): void {
    $output = $this->drupalGet('user/1');
    $this->assertStringContainsString('Member for', $output);

    $this->drupalGet('admin/config/system/rename-admin-paths');

    $this->submitForm([
      'admin_path' => 1,
      'admin_path_value' => 'backend',
      'user_path' => 1,
      'user_path_value' => 'member',
    ], 'Save configuration');

    $this->assertRenameAdminPathFormIsVisible();

    $this->assertSession()->fieldValueEquals('admin_path_value', 'backend');
    $this->assertSession()->fieldValueEquals('user_path_value', 'member');

    $output = $this->drupalGet('user/1');
    $this->assertStringContainsString('The requested page could not be found.', $output);

    $output = $this->drupalGet('member/1');
    $this->assertStringContainsString('Member for', $output);
  }

  /**
   * Test to confirm that the module settings form appears properly.
   */
  private function assertRenameAdminPathFormIsVisible(): void {
    $output = $this->getSession()->getPage()->getContent();
    $this->assertStringContainsString('Rename admin path', $output);
    $this->assertStringContainsString('Replace "admin" in admin path by', $output);
    $this->assertStringContainsString('Rename user path', $output);
    $this->assertStringContainsString('Replace "user" in user path by', $output);
  }

}
