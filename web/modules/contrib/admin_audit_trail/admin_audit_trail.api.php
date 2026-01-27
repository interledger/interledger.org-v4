<?php

/**
 * @file
 * Documentation for the Admin Audit Trail module.
 */

/**
 * Returns event log handlers.
 *
 * @return array
 *   An associative array, keyed by event type, and valued by handler info:
 *   - {string} title
 *     The title that describes the events logged by this handler.
 *     This handler's 'form_submit_callback' callback will be notified when a
 *     form is submitted that has an id as specified in this array. Optional.
 *   - {array} form_ids_regexp
 *     The same as form_ids, but instead of identical matches regular
 *     expressions can be specified.
 *   - {string} form_submit_callback
 *     Callback that's called when a form is submitted with a form id as
 *     specified in form_ids. The callback function profile:
 *
 *   Optional. Notice that events can also be manually created using the
 *   admin_audit_trail_save function.
 */
function hook_admin_audit_trail_handlers() {
  $handlers = [];

  return $handlers;
}

/**
 * Allows for the altering of the log array.
 *
 * @param array $log
 *   The log record to be altered. This record contains the following fields:
 *   - {string} type
 *     The event type. This is usually the object type that is described by this
 *     event. Example: 'node' or 'user'. Required.
 *   - {string} operation
 *     The operation being performed. Example: 'insert'. Required.
 *   - {string} description
 *     A textual description of the event. Required.
 *   - {string} ref_numeric
 *     Reference to numeric id. Optional.
 *   - {string} ref_char
 *     Reference to alphabetical id. Optional.
 */
function hook_admin_audit_trail_log_alter(array &$log) {
}
