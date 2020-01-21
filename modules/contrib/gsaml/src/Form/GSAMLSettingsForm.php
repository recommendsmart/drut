<?php

namespace Drupal\gsaml\Form;

use Drupal\gsaml\GSAML;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupType;
use Drupal\taxonomy\Entity\Term;

/**
 * Class GSAMLSettingsForm.
 */
class GSAMLSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'gsaml_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['gsaml.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $config = $this->config('gsaml.settings');

    // Allow user to select a Vocabulary and a Group Type.
    $vocs = [0 => '- None -'];
    foreach (Vocabulary::loadMultiple() as $vid => $voc) {
      $vocs[$vid] = $voc->label();
    }

    $vid = $config->get('vocabulary') !== NULL ? $config->get('vocabulary') : 0;
    if (!is_string($vid) || $vid === '0') {
      $form['vocabulary'] = [
        '#type' => 'select',
        '#title' => $this->t('Vocabulary'),
        '#options' => $vocs,
        '#default_value' => $vid,
        '#disabled' => $config->get('vocabulary') !== NULL && !empty($config->get('vocabulary')) ? 1 : 0,
      ];
    }
    else {
      $form['vocabulary'] = [
        '#type' => 'label',
        '#title' => $this->t('Vocabulary:') . ' ' . $vocs[$vid],
        '#title_display' => 'before',
        '#suffix' => '<br/>',
      ];
    }

    $group_types = [0 => '- None -'];
    foreach (GroupType::loadMultiple() as $gtid => $group_type) {
      $group_types[$gtid] = $group_type->label();
    }

    $gtid = $config->get('group_type') !== NULL ? $config->get('group_type') : 0;
    if (!is_string($gtid) || $gtid === '0') {
      $form['group_type'] = [
        '#type' => 'select',
        '#title' => $this->t('Group type'),
        '#options' => $group_types,
        '#default_value' => $gtid,
        '#disabled' => $config->get('group_type') !== NULL && !empty($config->get('group_type')) ? 1 : 0,
        '#states' => [
          'visible' => [
            ':input[name="vocabulary"]' => ['!value' => '0'],
          ],
        ],
        '#ajax' => [
          'callback' => '::group_fields_ajax_callback',
          'disable-refocus' => FALSE,
          'event' => 'change',
          'wrapper' => 'edit-output',
          'method' => 'replace',
          'progress' => [
            'type' => 'throbber',
            'message' => $this->t('Verifying entry...'),
          ],
        ],
      ];
    }
    else {
      $form['group_type'] = [
        '#type' => 'label',
        '#title' => t('Group type:') . ' ' . $group_types[$gtid],
        '#title_display' => 'before',
        '#suffix' => '<br/>',
      ];
    }

    $group_fields = [0 => '- None -'];
    $group_fields_aux = \Drupal::service('entity_field.manager')->getFieldDefinitions('group', $gtid);
    foreach ($group_fields_aux as $field) {
      if ($field->getType() === 'entity_reference') {
        $group_fields[$field->getName()] = $field->getLabel();
      }
    }

    $group_field = $config->get('group_field') !== NULL ? $config->get('group_field') : 0;
    if (!empty($group_fields) && count($group_fields) > 1) {
      $form['group_field'] = [
        '#type' => 'select',
        '#title' => $this->t('Group field to associate with taxonomy'),
        '#options' => $group_fields,
        '#default_value' => $group_field,
        '#states' => [
          'visible' => [
            ':input[name="vocabulary"]' => ['!value' => '0'],
            ':input[name="group_type"]' => ['!value' => '0'],
          ],
        ],
        '#attributes' => [
          'id' => ['edit-output'],
        ],
        '#validated' => TRUE,
      ];
    }
    else {
      $form['group_field'] = [
        '#type' => 'label',
        '#title' => $this->t('Please, add a field to \'Group\' of type \'Entity reference\', related to selected Vocabulary'),
        '#title_display' => 'before',
        '#suffix' => '<br/>',
      ];
    }

    // Get all installed plugins.
    $group_type = $config->get('group_type');
    if (!empty($group_type)) {
      $grelations = [];
      if (count(GroupType::load($group_type)->getInstalledContentPlugins()) > 1) {
        foreach (GroupType::load($group_type)->getInstalledContentPlugins() as $grelation) {
          $plugin_id = $grelation->getConfiguration()['id'];
          $gentity = strpos($plugin_id, ':') !== FALSE ? explode(':', $plugin_id)[1] : '';
          $types = strpos($plugin_id, ':') !== FALSE ? explode(':', $plugin_id)[0] : '';
          if (isset(explode('_', $types)[1]) && explode('_', $types)[1] != NULL) {
            $gtype[] = explode('_', $types)[1];
            $grelations[] = $gentity;
          }
        }

        $plugin_types = array_unique($gtype);
        foreach ($plugin_types as $type) {

          $entity_fields = [];
          for ($i = 0; $i < count($grelations); $i++) {
            if ($gtype[$i] != NULL && $grelations[$i] != NULL && $gtype[$i] == $type) {
              $entity_fields_aux = \Drupal::service('entity_field.manager')->getFieldDefinitions($gtype[$i], $grelations[$i]);
              foreach ($entity_fields_aux as $field) {
                if ($field->getType() == 'entity_reference') {
                  $entity_fields[$field->getName()] = $field->getLabel();
                }
              }
            }
          }

          $entity_id = $config->get('entity_id_' . $type) !== NULL ? $config->get('entity_id_' . $type) : 0;
          if (!empty($entity_fields) && count($entity_fields) > 1) {
            $form['entity_id_' . $type] = [
              '#type' => 'select',
              '#multiple' => TRUE,
              '#title' => $this->t("$type fields related to vocabulary"),
              '#options' => $entity_fields,
              '#default_value' => $entity_id,
            ];
          }
          else {
            $form['entity_id_' . $type] = [
              '#type' => 'label',
              '#title' => $this->t("Please, add a field to \'$type\' of type \'Entity Reference\' with the vocabulary target"),
              '#title_display' => 'before',
              '#suffix' => '<br/>',
            ];
          }

        }
      }
      else {
        $form['entity_id'] = [
          '#markup' => $this->t("Please, install plugin's in: admin/group/types/manage/$group_type/content"),
          '#suffix' => '<br/>',
        ];
      }
    }

    $user_fields = [0 => '- None -'];
    $user_fields_aux = \Drupal::service('entity_field.manager')->getFieldDefinitions('user', 'user');
    foreach ($user_fields_aux as $field) {
      if ($field->getType() === 'string_long') {
        $user_fields[$field->getName()] = $field->getLabel();
      }
    }

    $field_id = $config->get('user_field') !== NULL ? $config->get('user_field') : 0;
    if (!empty($user_fields) && count($user_fields) > 1) {
      $form['user_field'] = [
        '#type' => 'select',
        '#title' => $this->t('User field with ADFS roles'),
        '#options' => $user_fields,
        '#default_value' => $field_id,
        '#states' => [
          'visible' => [
            ':input[name="group_type"]' => ['!value' => '0'],
          ],
        ],
      ];
    }
    else {
      $form['user_field'] = [
        '#markup' => $this->t('Please, add a field to \'User\' of type \'Text (plain, long)\' with the user\'s ADFS roles'),
        '#suffix' => '<br/>',
      ];
    }

    $field_adfs = $config->get('user_adfs') !== NULL ? $config->get('user_adfs') : '';

    $form['user_adfs'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Claim with ADFS roles'),
        '#default_value' => $field_adfs,
    ];

    if ($vid !== '0' && $gtid !== '0') {
      $order_config = self::constructTreeTerms($vid, $config->get('table'));
      $form['table'] = self::renderTable($vid, $gtid, $order_config);
    }

    if ($config->get('table') !== NULL && !empty($config->get('table'))) {
      if ($config->get('group_field') !== NULL && !empty($config->get('group_field'))) {
        $form['actions']['update_tax'] = [
          '#type' => 'submit',
          '#value' => t('Associate group with term'),
          '#submit' => ['\Drupal\gsaml\Form\GSAMLSettingsForm::submitUpdateTax'],
        ];
      }
      $form['actions']['assoc_content'] = [
        '#type' => 'submit',
        '#value' => t('Associate Content'),
        '#submit' => ['\Drupal\gsaml\Form\GSAMLSettingsForm::submitAssocContent'],
      ];
      $form['actions']['assoc_members'] = [
        '#type' => 'submit',
        '#value' => t('Associate Members'),
        '#submit' => ['\Drupal\gsaml\Form\GSAMLSettingsForm::submitRebuildPerms'],
      ];
      $form['actions']['save_nodes'] = [
        '#type' => 'submit',
        '#value' => t('Save Nodes'),
        '#submit' => ['\Drupal\gsaml\Form\GSAMLSettingsForm::submitSaveNodes'],
      ];
      $form['actions']['save_media'] = [
        '#type' => 'submit',
        '#value' => t('Save Media'),
        '#submit' => ['\Drupal\gsaml\Form\GSAMLSettingsForm::submitSaveMedia'],
      ];
    }

    return $form;
  }

  /**
   * Build Tree by hierarchy.
   */
  protected function constructTreeTerms($vid, $config_table) {
    $tree = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid, 0, 1, TRUE);

    $order_config = [];
    foreach ($tree as $term) {
      $order_config[$term->id()] = [
        'label' => $term->label(),
        'value' => $config_table !== NULL && !empty($config_table) ? $config_table[$term->id()] : NULL,
      ];

      $subtree = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid, $term->id(), NULL, TRUE);
      foreach ($subtree as $subterm) {
        $order_config[$subterm->id()] = [
          'label' => '-- ' . $subterm->label(),
          'value' => $config_table !== NULL && !empty($config_table) ? $config_table[$subterm->id()] : NULL,
        ];
      }
    }
    return $order_config;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $table = $form_state->getValue('table');
    $this->configFactory->getEditable('gsaml.settings')
      ->set('table', $table)
      ->save();
    if ($this->configFactory->getEditable('gsaml.settings')->get('vocabulary') === NULL ||
      empty($this->configFactory->getEditable('gsaml.settings')->get('vocabulary'))) {
      $this->configFactory->getEditable('gsaml.settings')
        ->set('vocabulary', $form_state->getValue('vocabulary'))
        ->save();
    }
    if ($this->configFactory->getEditable('gsaml.settings')->get('group_type') === NULL ||
      empty($this->configFactory->getEditable('gsaml.settings')->get('group_type'))) {
      $this->configFactory->getEditable('gsaml.settings')
        ->set('group_type', $form_state->getValue('group_type'))
        ->save();
    }
    $this->configFactory->getEditable('gsaml.settings')
      ->set('user_field', $form_state->getValue('user_field'))
      ->save();
    $this->configFactory->getEditable('gsaml.settings')
      ->set('user_adfs', $form_state->getValue('user_adfs'))
      ->save();
    $this->configFactory->getEditable('gsaml.settings')
      ->set('entity_id_node', $form_state->getValue('entity_id_node'))
      ->save();
    $this->configFactory->getEditable('gsaml.settings')
      ->set('entity_id_media', $form_state->getValue('entity_id_media'))
      ->save();
    $this->configFactory->getEditable('gsaml.settings')
      ->set('group_field', $form_state->getValue('group_field'))
      ->save();

    if (empty($this->configFactory->getEditable('gsaml.settings')->get('group_type'))) {
      parent::submitForm($form, $form_state);
      return;
    }
    $mapping = [];
    $group_roles = GroupType::load($this->configFactory->getEditable('gsaml.settings')->get('group_type'));
    $group_roles = $group_roles->getRoleIds(FALSE);
    if (!is_array($table)) {
      parent::submitForm($form, $form_state);
      return;
    }
    foreach ($table as $term => $column) {
      if (!is_array($column)) {
        continue;
      }
      foreach ($column as $role => $fss) {
        if (empty($fss)) {
          continue;
        }
        $fss = explode(PHP_EOL, $fss);
        foreach ($fss as $fs) {
          $fs = trim($fs);
          if (empty($fs)) {
            continue;
          }
          $next_map = ['term' => $term];
          if (in_array($role, $group_roles)) {
            $next_map = array_merge($next_map, ['group_role' => $role, 'role' => '']);
          }
          else {
            $next_map = array_merge($next_map, ['group_role' => '', 'role' => $role]);
          }
          if (isset($mapping[$fs])) {
            $mapping[$fs] = array_merge($mapping[$fs], [$next_map]);
          }
          else {
            $mapping[$fs] = [$next_map];
          }
        }
      }
    }
    $this->configFactory->getEditable('gsaml.settings')
      ->set('mapping', $mapping)
      ->save();

    if (!empty($table)) {
      $config = $this->configFactory->getEditable('gsaml.settings');
      $tids = array_keys($table);
      $group_type = $config->get('group_type');
      $terms = Term::loadMultiple($tids);
      foreach ($terms as $term) {
        $conf_id = $config->get('mapping_terms.' . $term->id());
        if (!empty($conf_id)) {
          if (!empty(self::groupExist($term->id(), $term->label()))) {
            continue;
          }
        }
        $group = [];
        $group['langcode'] = 'en';
        $group['label'] = $term->label();
        $group['type'] = $group_type;
        $group['uid'] = 1;
        $new_group = Group::create($group);
        $new_group->save();
        $aux = $config->get('mapping_terms');
        if (empty($aux)) {
          $aux = [];
        }
        $aux = $aux + [$term->id() => $new_group->id()];
        $config->set('mapping_terms', $aux);
        $config->save();
      }
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * Verify if group exists.
   */
  public static function groupExist($id, $label) {
    $config = \Drupal::config('gsaml.settings');
    $field = $config->get('group_field');
    $field_target_id = $field . '_target_id';
    $query = \Drupal::database()->query("SELECT id FROM groups_field_data base_table
      LEFT JOIN group__$field voc ON  base_table.id = voc.entity_id
      WHERE base_table.label = '$label' or voc.$field_target_id = $id;");
    return $query->fetchAllKeyed(0, 0);
  }

  /**
   * Rebuild Permissions.
   */
  public static function submitRebuildPerms(array &$form, FormStateInterface $form_state) {
    // Create batch process.
    $config = \Drupal::config('gsaml.settings');
    $table = $config->get('table');
    $tids = array_keys($table);
    $process_n_terms = 10;
    $number = round(count($tids) / $process_n_terms, 0, PHP_ROUND_HALF_DOWN) + 1;
    $group_type = $config->get('group_type');
    $grelations = [];
    foreach (GroupType::load($group_type)->getInstalledContentPlugins() as $grelation) {
      $plugin_id = $grelation->getConfiguration()['id'];
      $gnode = strpos($plugin_id, ':') !== FALSE ? explode(':', $plugin_id)[1] : '';
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
    $batch = [
      'init_message' => t('Creating a group for each term...'),
      'operations' => [
        ['\Drupal\gsaml\GSAML::removeMemberships', [$input]],
        ['\Drupal\gsaml\GSAML::removeRoles', [$input]],
        ['\Drupal\gsaml\GSAML::associateUsersToGroupRoles', [$input]],
      ],
    ];
    batch_set($batch);
  }

  /**
   * Associate Content to Groups.
   */
  public static function submitAssocContent(array &$form, FormStateInterface $form_state) {
    // Create batch process.
    $config = \Drupal::config('gsaml.settings');
    $table = $config->get('table');
    $tids = array_keys($table);
    $process_n_terms = 10;
    $number = round(count($tids) / $process_n_terms, 0, PHP_ROUND_HALF_DOWN) + 1;
    $group_type = $config->get('group_type');
    $grelations = [];
    foreach (GroupType::load($group_type)->getInstalledContentPlugins() as $grelation) {
      $plugin_id = $grelation->getConfiguration()['id'];
      $gnode = strpos($plugin_id, ':') !== FALSE ? explode(':', $plugin_id)[1] : '';
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
    $batch = [
      'init_message' => t('Creating a group for each term...'),
      'operations' => [
        ['\Drupal\gsaml\GSAML::removeGroupContent', [$input]],
        ['\Drupal\gsaml\GSAML::associateContentToGroup', [$input]],
      ],
    ];
    batch_set($batch);
  }

  /**
   * Associate existing media to Group's.
   */
  public static function submitSaveMedia(array &$form, FormStateInterface $form_state) {
    // Create batch process.
    $n_nodes = GSAML::countEntityWithoutGroup('media');
    $input = [
      'n_nodes' => $n_nodes,
      'type' => 'media',
    ];
    $batch = [
      'init_message' => t('Saving media...'),
      'operations' => [
        ['\Drupal\gsaml\GSAML::saveEntity', [$input]],
      ],
    ];
    batch_set($batch);
  }

  /**
   * Associate existing nodes to Group's.
   */
  public static function submitSaveNodes(array &$form, FormStateInterface $form_state) {
    // Create batch process.
    $n_nodes = GSAML::countEntityWithoutGroup('node');
    $input = [
      'n_nodes' => $n_nodes,
      'type' => 'node',
    ];
    $batch = [
      'init_message' => t('Saving nodes...'),
      'operations' => [
        ['\Drupal\gsaml\GSAML::saveEntity', [$input]],
      ],
    ];
    batch_set($batch);
  }

  /**
   * Associate existing Group's to term.
   */
  public static function submitUpdateTax(array &$form, FormStateInterface $form_state) {
    // Create batch process.
    $vid = \Drupal::config('gsaml.settings')->get('vocabulary');
    $group_type = \Drupal::config('gsaml.settings')->get('group_type');
    $group_field = \Drupal::config('gsaml.settings')->get('group_field');
    $n_terms = GSAML::countTermsInVocabulary($vid);
    $input = [
      'n_terms' => $n_terms,
      'vid' => $vid,
      'group_type' => $group_type,
      'group_field' => $group_field,
    ];
    $batch = [
      'init_message' => t('Associating groups with terms...'),
      'operations' => [
        ['\Drupal\gsaml\GSAML::updateTax', [$input]],
      ],
    ];
    batch_set($batch);
  }

  /**
   * Render table markup.
   */
  public static function renderTable($vid, $gtid, $rows) {
    $table = [
      '#type' => 'table',
      '#empty' => t('Make sure you have at least a vocabulary and a group type.'),
      '#attributes' => [
        'id' => ['edit-output'],
      ],
    ];
    self::renderHeader($table, $gtid);
    self::renderRows($table, $table['#header'], $rows);
    return $table;
  }

  /**
   * Render table header markup.
   */
  public static function renderHeader(&$table, $gtid) {
    $group_type = GroupType::load($gtid);

    $header['areas'] = t('Area');
    if ($group_type) {
      foreach ($group_type->getRoles(FALSE) as $group_role) {
        $header[$group_role->id()] = $group_role->getGroupTypeId() . ':' . t('@group_role', array('@group_role' => $group_role->label()));
      }
    }
    foreach (user_roles(TRUE) as $role) {
      if ($role->id() === 'authenticated') {
        continue;
      }
      $header[$role->id()] = t('@role', array('@role' => $role->label()));
    }
    $table['#header'] = $header;
  }

  /**
   * Render table rows markup.
   */
  public static function renderRows(&$table, $header, $rows) {
    $header_keys = array_keys($header);
    $rows_keys = array_keys($rows);
    for ($i = 0; $i < count($header); $i++) {
      for ($j = 0; $j < count($rows_keys); $j++) {
        $hk = $header_keys[$i];
        $rk = $rows_keys[$j];
        $term = Term::load($rk);
        if (!$term) {
          continue;
        }
        if ($hk == 'areas') {
          $table[$rk][$hk] = [
            '#type' => 'label',
            '#title' => $rows[$rk]['label'],
            '#title_display' => 'before',
          ];
        }
        else {
          $table[$rk][$hk] = [
            '#type' => 'textarea',
            '#title' => 'Text',
            '#title_display' => 'hidden',
            '#value' => isset($rows[$rk],
            $rows[$rk]['value'][$hk]) ? $rows[$rk]['value'][$hk] : '',
            '#cols' => 60,
            '#rows' => 5,
            '#resizable' => 'vertical',
          ];
        }
      }
    }
  }

  /**
   * Form helper.
   */
  public function group_fields_ajax_callback($form, $form_state) {
    $gtid = $form['group_type']['#value'];
    $form['group_field']['#options'] = $this->groups_fields_update($gtid);
    unset($form['group_field']['#title']);
    return $form['group_field'];
  }

  /**
   * Get group fields.
   */
  public function groups_fields_update($gtid) {
    $group_fields[0] = '- None -';
    $group_fields_aux = \Drupal::service('entity_field.manager')->getFieldDefinitions('group', $gtid);
    foreach ($group_fields_aux as $field) {
      if ($field->getType() === 'entity_reference') {
        $group_fields[$field->getName()] = $field->getLabel();
      }
    }
    return $group_fields;
  }

}
