<?php

/**
 * @file
 * Views related hooks for Contacts Theme.
 */

use Drupal\views\ViewExecutable;
use Drupal\Component\Utility\Html;

/**
 * Implements hook_views_pre_render().
 */
function contacts_theme_views_pre_render(ViewExecutable $view) {
  if ($view->id() == 'contacts_dashboard_indexed') {
    $view->display_handler->setOption('css_class', 'app-flex flex-fill-scroll view-fill-scroll');
    if (isset($view->header['result'])) {
      $view->header['result']->options['content'] = '<div class="view-result-header">' . $view->header['result']->options['content'] . '</div>';
    }
  }

  $css_class = Html::getClass('views-view-' . $view->id()) . ' ' . $view->display_handler->getOption('css_class');
  $view->display_handler->setOption('css_class', $css_class);
}

/**
 * Implements hook_preprocess_views_view_table().
 */
function contacts_theme_preprocess_views_view_table(&$variables) {
  $variables['attributes']['class'][] = 'table';
  $variables['attributes']['class'][] = 'table-hover';

  $row_class_special = isset($options['row_class_special']) ? $options['row_class_special'] : TRUE;
  if ($row_class_special) {
    $variables['attributes']['class'][] = 'table-striped';
  }
}
