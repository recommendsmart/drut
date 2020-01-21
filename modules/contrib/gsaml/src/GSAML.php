<?php

namespace Drupal\gsaml;

use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupType;
use Drupal\node\Entity\Node;
use Drupal\media\MediaInterface;
use Drupal\media\Entity\Media;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;

/**
 * GSAML helper class.
 */
class GSAML {

  /**
   * Batch execution.
   */
  public static function associateContentToGroup($input, &$context) {
    $grelations = $input['grelations'];
    $process_n_nodes = $input['process_n_nodes'];
    $config_factory = \Drupal::configFactory();
    $config = $config_factory->getEditable('gsaml.settings');
    // In batch: For each content associated to that term(s), associate content
    // to corresponding group(s).
    if ($process_n_nodes && is_numeric($process_n_nodes)) {
      // Initiate multistep processing.
      if (empty($context['sandbox'])) {
        $context['sandbox']['progress'] = 0;
        $context['sandbox']['max'] = $process_n_nodes;
        $context['sandbox']['curr_node'] = 0;
      }

      // Process the next 100 if there are at least 100 left. Otherwise,
      // we process the remaining number.
      $batch_size = 1;
      $max = $context['sandbox']['progress'] + $batch_size;
      if ($max > $context['sandbox']['max']) {
        $max = $context['sandbox']['max'];
      }

      // Start where we left off last time.
      $start = $context['sandbox']['progress'];
      for ($i = $start; $i < $max; $i++) {
        // Update our progress!
        $context['sandbox']['progress']++;
        // Get next node without group.
        $next = self::getNIDWithTIDLargerThan($context['sandbox']['curr_node']);
        if (empty($next)) {
          $context['sandbox']['progress'] = $context['sandbox']['max'];
          $context['finished'] = 1;
          break;
        }
        $nid = array_keys($next);
        $nid = (int) reset($nid);
        $context['sandbox']['curr_node'] = $nid;
        $tids = reset($next);
        foreach (explode(',', $tids) as $tid) {
          $gid = $config->get('mapping_terms.' . $tid);
          if (!is_numeric($gid)) {
            continue;
          }
          $group = Group::load($gid);
          if (empty($group)) {
            continue;
          }
          $node = Node::load($nid);
          if (empty($node)) {
            continue;
          }
          $bundle = $node->getType();
          if (!in_array($bundle, $grelations)) {
            continue;
          }
          $gnode = $group->getContentByEntityId('group_node:' . $bundle, $nid);
          if (!empty($gnode)) {
            continue;
          }
          $group->addContent($node, 'group_node:' . $bundle);
        }
      }

      // Multistep processing : report progress.
      if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
        $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
      }
    }
  }

  /**
   * Count number of nodes.
   */
  public static function countNodes() {
    $query = \Drupal::database()->query('SELECT COUNT(*) FROM node;');
    $count = $query->fetchAllKeyed(0, 0);
    return reset($count);
  }

  /**
   * Get the next node larger than nid.
   */
  public static function getNIDWithTIDLargerThan($nid) {
    $next = [];
    if (is_numeric($nid)) {
      $query = \Drupal::database()->query('SELECT N.nid, GROUP_CONCAT(TI.tid) as tid FROM node N LEFT JOIN taxonomy_index TI ON TI.nid = N.nid WHERE N.nid > ' . $nid . ' GROUP BY N.nid ORDER BY nid ASC LIMIT 1;');
      $next = $query->fetchAllKeyed(0, 1);
      // nid, tids (multiple).
    }
    return $next;
  }

  /**
   * Associate Users to Group Roles and Roles.
   */
  public static function associateUsersToGroupRoles($input, &$context) {
    $process_n_users = $input['process_n_users'];
    $config_factory = \Drupal::configFactory();
    $config = $config_factory->getEditable('gsaml.settings');
    $field_name = $config->get('user_field');
    // In batch: For each user ADFS mapping, associate user to the corresponding
    // group(s), with the corresponding group role(s).
    if ($process_n_users && is_numeric($process_n_users) && !empty($field_name)) {
      // Initiate multistep processing.
      if (empty($context['sandbox'])) {
        $context['sandbox']['progress'] = 0;
        $context['sandbox']['max'] = $process_n_users;
        $context['sandbox']['curr_user'] = 0;
      }

      // Process the next 1 if there are at least 1 left. Otherwise,
      // we process the remaining number.
      $batch_size = 1;
      $max = $context['sandbox']['progress'] + $batch_size;
      if ($max > $context['sandbox']['max']) {
        $max = $context['sandbox']['max'];
      }

      // Start where we left off last time.
      $start = $context['sandbox']['progress'];
      for ($i = $start; $i < $max; $i++) {
        // Update our progress!
        $context['sandbox']['progress']++;
        // Get next node without group.
        $uid = self::getUIDLargerThan($context['sandbox']['curr_user']);
        if (empty($uid) || $uid == 0) {
          $context['sandbox']['progress'] = $context['sandbox']['max'];
          $context['finished'] = 1;
          break;
        }
        $context['sandbox']['curr_user'] = $uid;
        $user = User::load($uid);
        if (empty($user)) {
          continue;
        }
        // Get user FS in 'field_name'
        $fss = $user->get($field_name)->value;
        if (empty($fss)) {
          continue;
        }
        $fss = explode(PHP_EOL, $fss);
        foreach ($fss as $fs) {
          $fs = trim($fs);
          // Get FS mapping.
          $maps = $config->get('mapping.' . $fs);
          if (!is_array($maps)) {
            continue;
          }
          foreach ($maps as $map) {
            // Get group by term.
            $gid = $config->get('mapping_terms.' . $map['term']);
            if (empty($gid)) {
              continue;
            }
            // Load group.
            $group = Group::load($gid);
            if (empty($group)) {
              continue;
            }
            $guser = GroupContent::loadByEntity($user);
            // Add user to group with role.
            $group_roles = [];
            if (!empty($map['group_role'])) {
              $group_roles = ['group_roles' => $map['group_role']];
            }
            $gcid = self::getUserGroupContentsInGroup($user->id(), $gid);
            if (empty($gcid)) {
              $group->addMember($user, $group_roles);
            }
            else {
              $guser = GroupContent::load($gcid);
              if (!empty($guser)) {
                $roles = $guser->get('group_roles')->getValue();
                $rids = [];
                foreach ($roles as $role) {
                  $rids[] = $role['target_id'];
                }
                if (!empty($map['group_role'])) {
                  $rids[] = $map['group_role'];
                }
                $guser->set('group_roles', $rids);
                $guser->save();
              }
            }
            // If role is defined, add role to user.
            if (empty($map['role'])) {
              continue;
            }
            $user->addRole($map['role']);
            $user->save();
          }
        }
      }

      // Multistep processing : report progress.
      if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
        $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
      }
    }
  }

  /**
   * Count number of users.
   */
  public static function countUsers() {
    $query = \Drupal::database()->query('SELECT COUNT(*) FROM users U WHERE U.uid <> 0;');
    $count = $query->fetchAllKeyed(0, 0);
    return is_array($count) ? (int) reset($count) : $count;
  }

  /**
   * Get next user with UID larger than.
   */
  public static function getUIDLargerThan($uid) {
    $next = [0];
    if (is_numeric($uid)) {
      $query = \Drupal::database()->query('SELECT U.uid FROM users U WHERE U.uid <> 0 AND U.uid > ' . $uid . ' ORDER BY U.uid ASC LIMIT 1;');
      $next = $query->fetchAllKeyed(0, 0);
    }
    return (int) reset($next);
  }

  /**
   * Remove all members from groups.
   */
  public static function removeMemberships($input, &$context) {
    $process_n_users = $input['remove_gusers'];
    // In batch: Remove all members from all groups.
    if ($process_n_users && is_numeric($process_n_users)) {
      // Initiate multistep processing.
      if (empty($context['sandbox'])) {
        $context['sandbox']['progress'] = 0;
        $context['sandbox']['max'] = $process_n_users;
      }

      // Process the next 1 if there are at least 1 left. Otherwise,
      // we process the remaining number.
      $batch_size = 1;
      $max = $context['sandbox']['progress'] + $batch_size;
      if ($max > $context['sandbox']['max']) {
        $max = $context['sandbox']['max'];
      }

      // Start where we left off last time.
      $start = $context['sandbox']['progress'];
      for ($i = $start; $i < $max; $i++) {
        // Update our progress!
        $context['sandbox']['progress']++;
        // Get next node without group.
        $gcid = self::getNextUserWithGroup();
        if (empty($gcid)) {
          $context['sandbox']['progress'] = $context['sandbox']['max'];
          $context['finished'] = 1;
          break;
        }
        $guser = GroupContent::load($gcid);
        if (empty($guser)) {
          continue;
        }
        $guser->delete();
      }

      // Multistep processing : report progress.
      if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
        $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
      }
    }
  }

  /**
   * Count number of users that are in a group.
   */
  public static function countUsersWithGroup() {
    $query = \Drupal::database()->query('SELECT COUNT(*) FROM group_content_field_data G WHERE G.type LIKE \'%group_membership\';');
    $count = $query->fetchAllKeyed(0, 0);
    return is_array($count) ? (int) reset($count) : $count;
  }

  /**
   * Get the next user with a group.
   */
  public static function getNextUserWithGroup() {
    $query = \Drupal::database()->query('SELECT G.id FROM group_content_field_data G WHERE G.type LIKE \'%group_membership\' LIMIT 1;');
    $next = $query->fetchAllKeyed(0, 0);
    return is_array($next) ? (int) reset($next) : $next;
  }

  /**
   * Remove all roles from users.
   */
  public static function removeRoles($input, &$context) {
    $process_n_users = self::countUsersWithRole();
    // In batch: Remove all members from all groups.
    if ($process_n_users && is_numeric($process_n_users)) {
      // Initiate multistep processing.
      if (empty($context['sandbox'])) {
        $context['sandbox']['progress'] = 0;
        $context['sandbox']['max'] = $process_n_users;
      }

      // Process the next 1 if there are at least 1 left. Otherwise,
      // we process the remaining number.
      $batch_size = 1;
      $max = $context['sandbox']['progress'] + $batch_size;
      if ($max > $context['sandbox']['max']) {
        $max = $context['sandbox']['max'];
      }

      // Start where we left off last time.
      $start = $context['sandbox']['progress'];
      for ($i = $start; $i < $max; $i++) {
        // Update our progress!
        $context['sandbox']['progress']++;
        // Get next node without group.
        $next = self::getNextUserWithRole();
        if (empty($next)) {
          $context['sandbox']['progress'] = $context['sandbox']['max'];
          $context['finished'] = 1;
          break;
        }
        $rid = array_keys($next);
        $rid = reset($rid);
        $uid = (int) reset($next);
        $user = User::load($uid);
        if (empty($user)) {
          continue;
        }
        $user->removeRole($rid);
        $user->save();
      }

      // Multistep processing : report progress.
      if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
        $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
      }
    }
  }

  /**
   * Count number of users that have roles.
   */
  public static function countUsersWithRole() {
    $query = \Drupal::database()->query('SELECT COUNT(*) FROM user__roles UR WHERE UR.entity_id <> 1;');
    $count = $query->fetchAllKeyed(0, 0);
    return is_array($count) ? (int) reset($count) : $count;
  }

  /**
   * Get the next user with a role.
   */
  public static function getNextUserWithRole() {
    $query = \Drupal::database()->query('SELECT UR.roles_target_id, UR.entity_id FROM user__roles UR WHERE UR.entity_id <> 1 LIMIT 1;');
    $next = $query->fetchAllKeyed(0, 1);
    // rid, uid.
    return $next;
  }

  /**
   * Delete all group content.
   */
  public static function removeGroupContent($input, &$context) {
    $process_n_nodes = $input['remove_gnodes'];
    // In batch: For each content associated to that term(s), associate content
    // to corresponding group(s).
    if ($process_n_nodes && is_numeric($process_n_nodes)) {
      // Initiate multistep processing.
      if (empty($context['sandbox'])) {
        $context['sandbox']['progress'] = 0;
        $context['sandbox']['max'] = $process_n_nodes;
      }

      // Process the next 100 if there are at least 100 left. Otherwise,
      // we process the remaining number.
      $batch_size = 1;
      $max = $context['sandbox']['progress'] + $batch_size;
      if ($max > $context['sandbox']['max']) {
        $max = $context['sandbox']['max'];
      }

      // Start where we left off last time.
      $start = $context['sandbox']['progress'];
      for ($i = $start; $i < $max; $i++) {
        // Update our progress!
        $context['sandbox']['progress']++;
        // Get next node without group.
        $gcid = self::getNextGroupNode();
        if (empty($gcid)) {
          $context['sandbox']['progress'] = $context['sandbox']['max'];
          $context['finished'] = 1;
          break;
        }
        $gnode = GroupContent::load($gcid);
        if (empty($gnode)) {
          continue;
        }
        $gnode->delete();
      }

      // Multistep processing : report progress.
      if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
        $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
      }
    }
  }

  /**
   * Count number group contents.
   */
  public static function countGroupNodes() {
    $query = \Drupal::database()->query('SELECT COUNT(*) FROM group_content_field_data G WHERE G.type NOT LIKE \'%group_membership\';');
    $count = $query->fetchAllKeyed(0, 0);
    return is_array($count) ? (int) reset($count) : $count;
  }

  /**
   * Get the next group content.
   */
  public static function getNextGroupNode() {
    $query = \Drupal::database()->query('SELECT G.id FROM group_content_field_data G WHERE G.type NOT LIKE \'%group_membership\' LIMIT 1;');
    $next = $query->fetchAllKeyed(0, 0);
    return is_array($next) ? (int) reset($next) : $next;
  }

  /**
   * Batch execution.
   */
  public static function associateSingleContentToGroup($entity, $terms) {
    $config_factory = \Drupal::configFactory();
    $config = $config_factory->getEditable('gsaml.settings');
    foreach ($terms as $term) {

      $tid = $term;
      $gid = $config->get('mapping_terms.' . $tid);
      if (empty($gid)) {
        continue;
      }

      $group = Group::load($gid);
      if (empty($group)) {
        continue;
      }

      $group->addContent($entity, 'group_' . $entity->getEntityTypeId() . ':' . $entity->bundle());

    }
  }

  /**
   * Get the user membership.
   */
  public static function getUserGroupContents($uid) {
    $query = \Drupal::database()->query('SELECT G.id FROM group_content_field_data G WHERE G.type LIKE \'%group_membership\' AND G.entity_id = ' . $uid);
    $next = $query->fetchAllKeyed(0, 0);
    return is_array($next) ? $next : [$next];
  }

  /**
   * Get the next user with a group.
   */
  public static function getUserGroupContentsInGroup($uid, $gid) {
    $query = \Drupal::database()->query('SELECT G.id FROM group_content_field_data G WHERE G.type LIKE \'%group_membership\' AND G.entity_id = ' . $uid . ' AND G.gid = ' . $gid);
    $next = $query->fetchAllKeyed(0, 0);
    return is_array($next) ? (int) reset($next) : $next;
  }

  /**
   * Get the next user with a group.
   */
  public static function saveEntity($input, &$context) {
    $n_nodes = $input['n_nodes'];
    $type = $input['type'];
    if ($n_nodes && is_numeric($n_nodes)) {
      if (empty($context['sandbox'])) {
        $context['sandbox']['progress'] = 0;
        $context['sandbox']['max'] = $n_nodes;
        $context['sandbox']['curr_node'] = 0;
      }
      $max = $context['sandbox']['progress'] + 1;
      if ($max > $context['sandbox']['max']) {
        $max = $context['sandbox']['max'];
      }
      $nid = self::getNextEntityWithoutGroupLargerThan($context['sandbox']['curr_node'], $type);
      $context['sandbox']['curr_node'] = $nid;
      if ($type == 'media') {
        $entity = \Drupal\media\Entity\Media::load($nid);
      }
      else {
        $entity = Node::load($nid);
      }
      self::gsaml_entity_update($entity);
      $context['sandbox']['progress']++;
      if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
        $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
      }
    }
  }

  /**
   * Count entities with group.
   */
  public static function countEntityWithoutGroup($type) {
    $key = 'nid';
    if ($type == 'media') {
      $key = 'mid';
    }
    $query = \Drupal::database()->query("SELECT COUNT(*) FROM $type N LEFT JOIN group_content_field_data G ON G.entity_id = N.$key WHERE G.id IS NULL;");
    $count = $query->fetchAllKeyed(0, 0);
    return is_array($count) ? (int) reset($count) : (int) $count;
  }

  /**
   * Get next entity from group.
   */
  public static function getNextEntityWithoutGroupLargerThan($nid, $type) {
    if (is_numeric($nid)) {
      $key = 'nid';
      if ($type == 'media') {
        $key = 'mid';
      }
      $query = \Drupal::database()->query("SELECT N.$key FROM $type N LEFT JOIN group_content_field_data G ON G.entity_id = N.$key WHERE N.$key > " . $nid . " AND G.id IS NULL ORDER BY $key ASC LIMIT 1;");
      $next = $query->fetchAllKeyed(0, 0);
    }
    return is_array($next) ? (int) reset($next) : (int) $next;
  }

  /**
   * Entity update.
   */
  public static function gsaml_entity_update($entity) {
    // If gsaml.settings.yml is defined.
    $config_factory = \Drupal::configFactory();
    $config = $config_factory->getEditable('gsaml.settings');

    if (empty($config)) {
      return;
    }

    // Get Installed Plugins in Group Type defined in gsaml.settings.yml.
    $group_type = $config->get('group_type');
    if (empty($group_type)) {
      return;
    }

    $grelations = [];
    foreach (GroupType::load($group_type)->getInstalledContentPlugins() as $grelation) {
      $plugin_id = $grelation->getConfiguration()['id'];
      $gentity = strpos($plugin_id, ':') !== FALSE ? explode(':', $plugin_id)[1] : '';
      if (empty($gentity)) {
        continue;
      }
      $grelations[] = $gentity;
    }

    // Check if node exists in Installed Plugins.
    $bundle = $entity->bundle();

    if (!in_array($bundle, $grelations)) {
      return;
    }

    // If this node is in Installed Plugins, delete all group contents linked
    // to this node.
    $gentities = GroupContent::loadByEntity($entity);

    foreach ($gentities as $gentity) {
      $gentity->delete();
    }

    // Get all groups associated to this node terms (taxonomy).
    $terms = self::getEntityTerms($entity);

    self::associateSingleContentToGroup($entity, $terms);
  }

  /**
   * Get entity terms.
   */
  public static function getEntityTerms($entity) {
    $id = $entity->id();
    $config_factory = \Drupal::configFactory();
    $config = $config_factory->getEditable('gsaml.settings');

    if (empty($config)) {
      return;
    }
    $e_fields = $config->get('entity_id_' . $entity->getEntityTypeId());

    $next = [];
    if (is_numeric($id)) {
      foreach ($e_fields as $e_field) {
        $e_field_id = $e_field . '_target_id';
        $e_field_table = $entity->getEntityTypeId() . '__' . $e_field;
        $query = \Drupal::database()->query("SELECT TI.$e_field_id FROM $e_field_table TI WHERE TI.entity_id  = $id;");
        $keys = $query->fetchAllKeyed(0, 0);
        foreach ($keys as $key) {
          $next[$key] = $key;
        }
      }
    }
    return array_keys($next);
  }

  /**
   * Update groups with terms.
   */
  public static function updateTax($input, &$context) {
    $n_terms = $input['n_terms'];
    $vid = $input['vid'];
    $group_field = $input['group_field'];
    $config_factory = \Drupal::configFactory();
    $config = $config_factory->getEditable('gsaml.settings');
    if ($n_terms && is_numeric($n_terms) && !empty($vid)) {
      if (empty($context['sandbox'])) {
        $context['sandbox']['progress'] = 0;
        $context['sandbox']['max'] = $n_terms;
        $context['sandbox']['curr_term'] = 0;
      }
      $max = $context['sandbox']['progress'] + 1;
      if ($max > $context['sandbox']['max']) {
        $max = $context['sandbox']['max'];
      }
      $tid = self::getNextTermLargerThan($context['sandbox']['curr_term'], $vid);
      // Get group from mapping.
      $gid = $config->get('mapping_terms.' . $tid);
      if (!empty($gid)) {
        $group = Group::load($gid);
        if (!empty($group)) {
          // Update field '$group_type' with tid.
          $term = Term::load($tid);
          $group->set($group_field, $term);
          // Save group.
          $group->save();
        }
      }
      $context['sandbox']['curr_term'] = $tid;
      $context['sandbox']['progress']++;
      if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
        $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
      }
    }
  }

  /**
   * Count terms with vid.
   */
  public static function countTermsInVocabulary($vid) {
    $query = \Drupal::database()->query('SELECT COUNT(*) FROM taxonomy_term_data T WHERE T.vid = \'' . $vid . '\';');
    $count = $query->fetchAllKeyed(0, 0);
    return is_array($count) ? (int) reset($count) : (int) $count;
  }

  /**
   * Get next term.
   */
  public static function getNextTermLargerThan($tid, $vid) {
    if (is_numeric($tid)) {
      $query = \Drupal::database()->query('SELECT T.tid FROM taxonomy_term_data T WHERE T.vid = \'' . $vid . '\' AND T.tid > ' . $tid . ' ORDER BY T.tid ASC LIMIT 1;');
      $next = $query->fetchAllKeyed(0, 0);
    }
    return is_array($next) ? (int) reset($next) : (int) $next;
  }

  /**
   * Remove users from groups.
   */
  public static function removeUserMemberships($account) {
    $roles = $account->getRoles();
    foreach ($roles as $id => $role) {
      $account->removeRole($role);
    }
    $account->save();

    $gcids = GSAML::getUserGroupContents($account->id());
    foreach ($gcids as $gcid) {
      $guser = GroupContent::load($gcid);
      if (empty($guser)) {
        continue;
      }
      $guser->delete();
    }
  }


  /**
   * Batch execution.
   */
  public static function associateSingleUserToGroup($user, $fss = NULL) {

    $config_factory = \Drupal::configFactory();
    $config = $config_factory->getEditable('gsaml.settings');
    $field_name = $config->get('user_field');

    if ($fss === NULL) {
      $fss = $user->get($field_name)->value;

      if (!is_array($fss)) {
        $fss = explode(PHP_EOL, $fss);
      }
    }

    foreach ($fss as $fs) {
      $fs = trim($fs);
      $maps = $config->get('mapping.' . $fs);

      if (!is_array($maps)) {
        continue;
      }
      foreach ($maps as $map) {
        // Get group by term.
        $gid = $config->get('mapping_terms.' . $map['term']);

        if (!is_numeric($gid)) {
          continue;
        }

        // Load group.
        $group = Group::load($gid);

        if (empty($group)) {
          continue;
        }
        // Add user to group with role.
        $group_roles = [];
        if (!empty($map['group_role'])) {
          $group_roles = ['group_roles' => $map['group_role']];
        }
        $gcid = self::getUserGroupContentsInGroup($user->id(), $gid);

        if (empty($gcid)) {
          $group->addMember($user, $group_roles);
        }
        else {
          $guser = GroupContent::load($gcid);
          if (!empty($guser)) {
            $roles = $guser->get('group_roles')->getValue();
            $rids = [];
            foreach ($roles as $role) {
              $rids[] = $role['target_id'];
            }
            if (!empty($map['group_role'])) {
              $rids[] = $map['group_role'];
            }
            $guser->set('group_roles', $rids);
            $guser->save();
          }
        }
        // If role is defined, add role to user.
        if (!empty($map['role'])) {
          $user->addRole($map['role']);
          $user->save();
        }
      }
    }
  }

}
