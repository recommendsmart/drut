<?php

namespace Drupal\crm_core_match\Plugin\crm_core_match\engine;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\crm_core_contact\ContactInterface;
use Drupal\crm_core_match\Plugin\crm_core_match\field\FieldHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * DefaultMatchingEngine class.
 *
 * Extends CrmCoreMatchEngine to provide rules for identifying duplicate
 * contacts.
 *
 * @CrmCoreMatchEngine(
 *   id = "default",
 *   label = @Translation("Default Matching Engine"),
 *   summary = @Translation("This is a simple matching engine from CRM Core. Allows administrators to specify matching rules for individual contact types on a field-by-field basis."),
 * )
 */
class DefaultMatchingEngine extends MatchEngineBase {

  const MATCH_CHARS_DEFAULT = 3;

  /**
   * The match field plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $pluginManager;

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a default matching engine.
   */
  public function __construct($configuration, $id, $definition, PluginManagerInterface $plugin_manager, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager) {
    parent::__construct($configuration, $id, $definition);
    $this->pluginManager = $plugin_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.crm_core_match.match_field'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function match(ContactInterface $contact) {
    $ids = [];

    $fields = $contact->getFieldDefinitions();
    $results = [];
    $configuration_rules = $this->getConfigurationItem('rules') ?: [];
    foreach ($configuration_rules as $name => $rules) {
      if (isset($fields[$name])) {
        $rules['field'] = $fields[$name];

        if (!$this->pluginManager->hasDefinition($rules['field']->getType())) {
          continue;
        }

        /* @var \Drupal\crm_core_match\Plugin\crm_core_match\field\FieldHandlerInterface $field_handler */
        $field_handler = $this->pluginManager->createInstance($rules['field']->getType(), $rules);

        foreach ($field_handler->getPropertyNames() as $name) {
          $result = $field_handler->match($contact, $name);
          $keys = array_keys($result);
          $key = reset($keys);
          if (isset($results[$key])) {
            if (isset($results[$key][$name])) {
              $results[$key][$name] += $result[$key][$name];
            }
            else {
              $results[$key] += $result[$key];
            }
          }
          else {
            $results += $result;
          }
        }
      }
    }
    foreach ($results as $id => $rule_matches) {
      $total_score = array_sum($rule_matches);
      if ($total_score >= $this->getConfigurationItem('threshold')) {
        $ids[] = $id;
      }
    }

    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['threshold'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Threshold'),
      '#description' => $this->t('Defines the score at which a contact is considered a match.'),
      '#maxlength' => 28,
      '#size' => 28,
      '#required' => TRUE,
      '#default_value' => $this->getConfigurationItem('threshold'),
    ];

    $return_description = $this->t('If two or more contact records result in matches with identical scores, CRM Core will give preference to one over the other base on selected option.');
    $form['return_order'] = [
      '#type' => 'select',
      '#title' => $this->t('Return Order'),
      '#description' => $return_description,
      '#default_value' => $this->getConfigurationItem('return_order'),
      '#options' => [
        'created' => $this->t('Most recently created'),
        'updated' => $this->t('Most recently updated'),
        'associated' => $this->t('Associated with user'),
      ],
    ];

    $strict_description = $this->t('Check this box to return a match for this contact type the first time one is identified that meets the threshold. Stops redundant processing.');
    $form['strict'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Strict matching'),
      '#description' => $strict_description,
      '#default_value' => $this->getConfigurationItem('strict'),
    ];

    $form['rules'] = [
      '#title' => $this->t('Field Matching'),
      '#type' => 'table',
      '#tree' => TRUE,
      '#header' => $this->buildHeader(),
      '#empty' => $this->t('There are no fields available.'),
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'weight',
        ],
      ],
      '#theme_wrappers' => ['form_element'],
    ];

    $form['unsupported'] = [
      '#type' => 'details',
      '#title' => $this->t('Unsupported Fields'),
      '#open' => TRUE,
      // Hide table when empty. We set this to TRUE as soon there is at least
      // one unsupported field.
      '#access' => FALSE,
      'table' => [
        '#theme' => 'table',
        '#header' => [
          'label' => $this->t('Name'),
          'field_type' => $this->t('Field type'),
        ],
        '#rows' => [],
        '#empty' => $this->t('There are no unsupported fields.'),
      ],
      'help_text' => [
        '#theme_wrappers' => ['container'],
        '#attributes' => ['class' => ['description']],
        '#markup' => $this->t('A given field is unsupported when there is no field match handler for the field type it belongs to. In order to make it supported a handler plugin should be created for it.'),
      ],
    ];

    // @todo: Display fields per bundle.
    $contact_types = $this->entityTypeManager->getStorage('crm_core_individual_type')->loadMultiple();
    $fields = [];
    foreach ($contact_types as $contact_type_id => $value) {
      $fields += $this->entityFieldManager->getFieldDefinitions('crm_core_individual', $contact_type_id);
    }
    foreach ($fields as $field) {

      $rules = $this->getConfigurationItem('rules');
      $config = empty($rules[$field->getName()]) ? [] : $rules[$field->getName()];
      $config['field'] = $field;

      if ($this->pluginManager->hasDefinition($field->getType())) {
        $match_field_id = $field->getType();

        /* @var \Drupal\crm_core_match\Plugin\crm_core_match\field\FieldHandlerInterface $match_field */
        $match_field = $this->pluginManager->createInstance($match_field_id, $config);

        foreach ($match_field->getPropertyNames($field) as $name) {
          $row = $this->buildRow($match_field, $name, FALSE);
          $form['rules'][$field->getName() . ':' . $name] = $row;
        }
      }
      else {
        $form['unsupported']['table']['#rows'][] = [
          'label' => $field->getLabel(),
          'type' => $field->getType(),
        ];
        $form['unsupported']['#access'] = TRUE;
      }
    }

    return $form;
  }

  /**
   * Builds the header row for the rule listing.
   *
   * @return array
   *   A render array structure of header strings.
   */
  public function buildHeader() {
    $header = [];

    $header['status'] = $this->t('Enabled');
    $header['label'] = $this->t('Name');
    $header['field_type'] = $this->t('Field type');
    $header['operator'] = $this->t('Operator');
    $header['options'] = $this->t('Options');
    $header['score'] = $this->t('Score');
    $header['weight'] = $this->t('Weight');

    return $header;
  }

  /**
   * Builds a row for an rule in the rule listing.
   *
   * @param \Drupal\crm_core_match\Plugin\crm_core_match\field\FieldHandlerInterface $field
   *   The match field of this rule.
   * @param string $name
   *   The property name of this rule.
   * @param bool $disabled
   *   Disables the form elements.
   *
   * @return array
   *   A render array structure of fields for this rule.
   */
  public function buildRow(FieldHandlerInterface $field, $name, $disabled) {
    $row = [];
    $row['#attributes']['class'][] = 'draggable';
    $row['#weight'] = $field->getWeight($name);

    $row['status'] = [
      '#type' => 'checkbox',
      '#default_value' => $field->getStatus($name),
      '#disabled' => $disabled,
    ];

    $row['label'] = [
      '#markup' => $field->getLabel($name),
    ];

    $row['type'] = [
      '#markup' => $field->getType(),
    ];

    $row['operator'] = [
      '#type' => 'select',
      '#default_value' => $field->getOperator($name),
      '#empty_option' => !$disabled ? NULL : $this->t('- Please Select -'),
      '#options' => $field->getOperators($name),
      '#disabled' => $disabled,
    ];

    $row['options'] = [
      '#type' => 'textfield',
      '#maxlength' => 28,
      '#size' => 28,
      '#default_value' => $field->getOptions($name),
      '#disabled' => $disabled,
    ];

    $row['score'] = [
      '#type' => 'textfield',
      '#maxlength' => 4,
      '#size' => 3,
      '#default_value' => $field->getScore($name),
      '#disabled' => $disabled,
    ];

    $row['weight'] = [
      '#type' => 'weight',
      '#title' => $this->t('Weight for @field', ['@field' => $field->getLabel()]),
      '#title_display' => 'invisible',
      '#default_value' => $field->getWeight($name),
      '#attributes' => ['class' => ['weight']],
    ];

    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);

    if (!is_numeric($form_state->getValue(['configuration', 'threshold']))) {
      $form_state->setErrorByName('configuration[threshold]', $this->t('Threshold must be a number.'));
    }
    $rules = $form_state->getValue(['configuration', 'rules']);
    foreach ($rules as $field_name => $config) {
      if ($config['status'] && empty($config['operator'])) {
        $name = 'rules][' . $field_name . '][operator';
        $message = $this->t('You must select an operator for enabled field.');
        $form_state->setErrorByName($name, $message);
      }
      if (!is_numeric($config['score'])) {
        $name = 'rules][' . $field_name . '][score';
        $message = $this->t('You must enter number in "Score" column.');
        $form_state->setErrorByName($name, $message);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // @todo: Build the same form and configuration structure.
    $rules = [];

    $this->configuration = $form_state->getValue('configuration');
    foreach ($form_state->getValue(['configuration', 'rules']) as $name => $config) {
      if (strpos($name, ':') !== FALSE) {
        list($parent, $child) = explode(':', $name, 2);
        $rules[$parent][$child] = $config;
      }
      else {
        $rules[$name] = $config;
      }
    }
    $this->configuration['rules'] = $rules;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    // TODO: Implement defaultConfiguration() method.
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    // TODO: Implement calculateDependencies() method.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getRules() {
    $rules = [];

    // Collect all fields of all contact types.
    $contact_types = $this->entityTypeManager->getStorage('crm_core_individual_type')->loadMultiple();
    $field_definitions = [];
    foreach ($contact_types as $contact_type_id => $value) {
      $field_definitions += $this->entityFieldManager->getFieldDefinitions('crm_core_individual', $contact_type_id);
    }

    foreach ($this->getConfigurationItem('rules') as $field_name => $field_settings) {
      // Skip fields that got dropped and disabled on matcher.
      if (!isset($field_definitions[$field_name]) || !$this->isEnabled($field_settings)) {
        continue;
      }

      /** @var \Drupal\Core\Field\FieldDefinitionInterface $field_definition */
      $field_definition = $field_definitions[$field_name];
      $rules[$field_name] = [
        'label' => $field_definition->getLabel(),
        'definition' => $field_definition,
      ];
    }

    return $rules;
  }

  /**
   * A helper function to check if there are enabled field properties.
   *
   * @param array $properties
   *   An array of field properties.
   *
   * @return bool
   *   Returns TRUE if there is at least one enabled property. Otherwise, FALSE.
   */
  protected function isEnabled(array $properties) {
    return (bool) array_filter(array_map(function ($property) {
      return $property['status'];
    }, $properties));
  }

}
