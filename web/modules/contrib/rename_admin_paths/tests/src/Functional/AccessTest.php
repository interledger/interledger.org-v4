<?php

declare(strict_types=1);

namespace Drupal\Tests\rename_admin_paths\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test to confirm access permissions.
 *
 * @group rename_admin_paths
 */
class AccessTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['rename_admin_paths'];

  /**
   * Test that the admin is still protected after renaming it.
   */
  public function testAdminNotAccessibleAfterRenaming(): void {
    $output = $this->drupalGet('admin');
    $this->assertStringContainsString('You are not authorized to access this page.', $output);

    $output = $this->drupalGet('admin/modules');
    $this->assertStringContainsString('You are not authorized to access this page.', $output);

    $admin = $this->drupalCreateUser(['administer modules', 'administer site configuration'], 'administrator', TRUE);
    $this->drupalLogin($admin);

    $this->drupalGet('admin/config/system/rename-admin-paths');

    $this->submitForm([
      'admin_path' => 1,
      'admin_path_value' => 'backend',
      'user_path' => 0,
      'user_path_value' => 'member',
    ], 'Save configuration');

    $this->drupalLogout();

    $output = $this->drupalGet('backend');
    $this->assertStringContainsString('You are not authorized to access this page.', $output);

    $output = $this->drupalGet('backend/modules');
    $this->assertStringContainsString('You are not authorized to access this page.', $output);
  }

}
