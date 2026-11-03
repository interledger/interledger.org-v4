<?php

/**
 * @file
 * Captcha updates once other modules have made their own updates.
 */

/**
 * Ensure the container cache is cleared.
 */
function captcha_post_update_refresh_captcha_helper_service() {
  drupal_flush_all_caches();
}

/**
 * Remove plain-text session IDs stored by earlier CAPTCHA sessions.
 */
function captcha_post_update_clear_plain_session_ids() {
  // New sessions store a hash of the session ID. The column is never read,
  // so clearing the old values is safe.
  \Drupal::database()->update('captcha_sessions')
    ->fields(['sid' => ''])
    ->execute();
}
