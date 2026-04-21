<?php

/**
 * @file
 * Post update functions for ln_c_cardgrid module.
 */

/**
 * Implements hook_removed_post_updates().
 */
function ln_c_cardgrid_removed_post_updates(): array {
  return [
    'ln_c_cardgrid_post_update_refactor_fields_migrate' => '3.0.0',
  ];
}
