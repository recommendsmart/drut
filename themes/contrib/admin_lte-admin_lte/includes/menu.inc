<?php
/**
 * @file
 * Theme and preprocess functions for menus
 */

function admin_lte_preprocess_menu__sidebar(&$variables) {
  _admin_lte_set_menu_items_icon($variables['items']);
}

function _admin_lte_set_menu_items_icon(&$items) {
  $icons = [
    'system.admin_content' => 'fa fa-file',
    'system.admin_structure' => 'fa fa-gears',
    'system.themes_page' => 'fa fa-tree',
    'system.modules_list' => 'fa fa-cubes',
    'system.admin_config' => 'fa fa-wrench',
    'entity.user.collection' => 'fa fa-user',
    'system.admin_reports' => 'fa fa-file-text-o',
    'help.main' => 'fa fa-question',
  ];
  foreach ($items as $key => $item) {
    if (isset($icons[$key])) {
      $items[$key]['icon'] = $icons[$key];
    }
    if (!empty($item['below'])) {
      _admin_lte_set_menu_items_icon($item['below']);
    }
  }
}
