<?php

/**
 * @file
 * Post update functions for dsu_c_text module.
 */

/**
 * Implements hook_removed_post_updates().
 */
function dsu_c_text_removed_post_updates(): array {
  return [
    'dsu_c_text_post_update_refactor_fields_migrate' => '3.0.0',
  ];
}
