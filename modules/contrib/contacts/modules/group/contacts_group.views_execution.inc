<?php

/**
 * @file
 * Views execution hook implementations for Contacts Group.
 */

use Drupal\views\ViewExecutable;

/**
 * Implements hook_views_pre_build().
 */
function contacts_group_views_pre_build(ViewExecutable $view) {
  if ($view->id() == 'contacts_orgs') {
    // Include the current page in the edit link URL.
    $page = pager_find_page($view->getPager()->getPagerId());
    $view->field['nothing']->options['alter']['path'] .= '&page=' . $page;
  }
}
