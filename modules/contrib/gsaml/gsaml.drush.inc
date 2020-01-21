<?php

use Drupal\gsaml\GSAML;
use Drupal\group\Entity\GroupType;

function gsaml_drush_command() {
  $items = array();
  $items['rebuild-gsaml'] = array(
    'description' => 'Rebuild GSAML',
    'examples' => array(
      'drush rebuild-gsaml' => 'Rebuild GSAML',
    ),
  );
  return $items;
}

function drush_gsaml_rebuild_gsaml() {
  drush_print('Rebuilding GSAML');
  // Create batch process
  $config = \Drupal::config('gsaml.settings');
  if (empty($config)) {
    return;
  }
  $table = $config->get('table');
  $tids = array_keys($table);
  $process_n_terms = 10;
  $number = round(count($tids) / $process_n_terms, 0, PHP_ROUND_HALF_DOWN) + 1;
  $group_type = $config->get('group_type');
  $grelations = [];
  foreach (GroupType::load($group_type)->getInstalledContentPlugins() as $grelation) {
    $pluginId = $grelation->getConfiguration()['id'];
    $gnode = strpos($pluginId, ':') !== FALSE ? explode(':', $pluginId)[1] : '';
    if (empty($gnode)) {
      continue;
    }
    $grelations[] = $gnode;
  }
  $process_n_nodes = GSAML::countNodes();
  $user_field = \Drupal::config('gsaml.settings')->get('user_field');
  $process_n_users = GSAML::countUsers();
  $remove_gnodes = GSAML::countGroupNodes();
  $remove_gusers = GSAML::countUsersWithGroup();
  $input = [
    'number' => $number,
    'process_n_terms' => $process_n_terms,
    'tids' => $tids,
    'table' => $table,
    'group_type' => $group_type,
    'grelations' => $grelations,
    'process_n_nodes' => $process_n_nodes,
    'user_field' => $user_field,
    'process_n_users' => $process_n_users,
    'remove_gnodes' => $remove_gnodes,
    'remove_gusers' => $remove_gusers,
  ];
  $operations = [
      ['\Drupal\gsaml\GSAML::createTermGroups', [$input]],
      ['\Drupal\gsaml\GSAML::removeGroupContent', [$input]],
      ['\Drupal\gsaml\GSAML::associateContentToGroup', [$input]],
      ['\Drupal\gsaml\GSAML::removeMemberships', [$input]],
      ['\Drupal\gsaml\GSAML::removeRoles', [$input]],
      ['\Drupal\gsaml\GSAML::associateUsersToGroupRoles', [$input]],
  ];
  $batch = array(
    'operations' => $operations,
    'finished' => 'importingmatch_finishedBatch',
    'title' => t('Import match'),
    'init_message' => t('Creating a group for each term...'),
    'progress_message' => t('Completed @current step of @total.'),
    'error_message' => t('Import match deletion has encountered an error.'),
  );

  // Initialize the batch.
  batch_set($batch);

  // Start the batch process.
  drush_backend_batch_process();
}



