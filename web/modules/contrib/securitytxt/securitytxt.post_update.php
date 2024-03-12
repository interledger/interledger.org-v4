<?php

/**
 * @file
 * Post update functions for securitytxt.
 */

/**
 * Remove misnamed config settings.
 */
function securitytxt_post_update_clear_misnamed_config(&$sandbox): void {
  \Drupal::configFactory()
    ->getEditable('securitytxt.settings')
    ->clear('encryption_public_key_url')
    ->clear('policy_page_url')
    ->clear('acknowledgement_page_url')
    ->save(TRUE);
}
