<?php

namespace Drupal\entity_extra_field\Form;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Define entity extra field form.
 */
class EntityExtraFieldForm extends EntityForm {

  /**
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheDiscovery;

  /**
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $extraFieldTypeManager;

  /**
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * Define the extra field type manager.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_discovery_backend
   *   The cache discovery backend service.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $extra_field_type_manager
   *   The extra field type plugin manager.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator
   *   The cache tags invalidator service.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   */
  public function __construct(
    CacheBackendInterface $cache_discovery_backend,
    PluginManagerInterface $extra_field_type_manager,
    CacheTagsInvalidatorInterface $cache_tags_invalidator,
    EntityDisplayRepositoryInterface $entity_display_repository
  ) {
    $this->cacheDiscovery = $cache_discovery_backend;
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
    $this->extraFieldTypeManager  = $extra_field_type_manager;
    $this->entityDisplayRepository = $entity_display_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static (
      $container->get('cache.discovery'),
      $container->get('plugin.manager.extra_field_type'),
      $container->get('cache_tags.invalidator'),
      $container->get('entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\entity_extra_field\Entity\EntityExtraField $entity */
    $entity = $this->entity;

    $form = parent::form($form, $form_state);

    $form['#parents'] = [];
    $form['#prefix'] = '<div id="entity-extra-field">';
    $form['#suffix'] = '</div>';

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Field Name'),
      '#maxlength' => 255,
      '#default_value' => $entity->label(),
      '#description' => $this->t('Input the extra field name.'),
      '#required' => TRUE,
    ];
    $form['name'] = [
      '#type' => 'machine_name',
      '#machine_name' => [
        'exists' => [$entity, 'exists'],
      ],
      '#disabled' => !$entity->isNew(),
      '#default_value' => $entity->name(),
    ];
    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => $entity->description()
    ];
    $field_type_id = $this->getEntityFormStateValue('field_type_id', $form_state);

    $form['field_type_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Field Type'),
      '#required' => TRUE,
      '#options' => $this->getExtraFieldTypeOptions(),
      '#empty_option' => $this->t('- Select -'),
      '#default_value' => $field_type_id,
      '#ajax' => [
        'event' => 'change',
        'method' => 'replace',
        'wrapper' => 'entity-extra-field',
        'callback' => [$this, 'entityExtraFieldAjax']
      ],
    ];

    if (isset($field_type_id) && !empty($field_type_id)) {
      $field_type_instance = $this->createFieldTypeInstance($field_type_id, $form_state);

      if ($field_type_instance !== FALSE
        && $field_type_instance instanceof PluginFormInterface) {
        $subform = ['#parents' => ['field_type_config']];

        $form['field_type_config'] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Field Type Configuration'),
          '#tree' => TRUE,
        ];
        $form['field_type_config'] += $field_type_instance->buildConfigurationForm(
          $subform,
          SubformState::createForSubform($subform, $form, $form_state)
        );
      }
    }
    $form['options'] = [
      '#type' => 'vertical_tabs',
    ];
    $form['display'] = [
      '#type' => 'details',
      '#title' => $this->t('Display'),
      '#group' => 'options',
      '#tree' => TRUE,
    ];
    $form['display']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Display Type'),
      '#required' => TRUE,
      '#options' => [
        'form' => $this->t('Form'),
        'view' => $this->t('View')
      ],
      '#empty_option' => $this->t('- Select -'),
      '#default_value' => $this->getEntityFormStateValue(
        ['display', 'type'],
        $form_state
      ),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    if ($field_type_id = $form_state->getValue('field_type_id')) {
      $field_type_instance = $this->createFieldTypeInstance($field_type_id, $form_state);

      if ($field_type_instance !== FALSE
        && $field_type_instance instanceof PluginFormInterface) {
        $subform = ['#parents' => ['field_type_config']];

        $field_type_instance->validateConfigurationForm(
          $subform,
          SubformState::createForSubform($subform, $form, $form_state)
        );
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($field_type_id = $form_state->getValue('field_type_id')) {
      $field_type_instance = $this->createFieldTypeInstance($field_type_id, $form_state);

      if ($field_type_instance !== FALSE
        && $field_type_instance instanceof PluginFormInterface) {
        $subform = ['#parents' => ['field_type_config']];

        $field_type_instance->submitConfigurationForm(
          $subform,
          SubformState::createForSubform($subform, $form, $form_state)
        );

        $form_state->setValue(
          'field_type_config',
          $field_type_instance->getConfiguration()
        );
      }
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * Ajax callback for entity extra field.
   *
   * @param array $form
   *   An array of form elements.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   A form state instance.
   *
   * @return array
   *   An array of the form elements.
   */
  public function entityExtraFieldAjax(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\entity_extra_field\Entity\EntityExtraField $entity */
    $entity = $this->entity;

    $status = parent::save($form, $form_state);

    $form_state->setRedirectUrl($entity->toUrl('collection'));

    $this->flushAllCaches();

    return $status;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityFromRouteMatch(RouteMatchInterface $route_match, $entity_type_id) {
    if ($route_match->getRawParameter($entity_type_id) !== NULL) {
      $entity = $route_match->getParameter($entity_type_id);
    }
    else {
      $values = [];
      $type_manager = $this->entityTypeManager;

      if ($base_entity_type_id = $route_match->getParameter('entity_type_id')) {
        $definition = $type_manager->getDefinition(
          $base_entity_type_id
        );
        $values['base_entity_type_id'] = $base_entity_type_id;

        $bundle_type = $definition->getBundleEntityType();
        if ($base_bundle_type = $route_match->getParameter($bundle_type)) {
          $values['base_bundle_type_id'] = $base_bundle_type->id();
        }
      }
      $entity = $type_manager->getStorage($entity_type_id)->create($values);
    }

    return $entity;
  }

  /**
   * Create extra field type plugin instance.
   *
   * @param $plugin_id
   *   The field type plugin identifier.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state instance.
   *
   * @return \Drupal\entity_extra_field\ExtraFieldTypePluginInterface|FALSE
   *   Return the extra field type plugin instance; otherwise FALSE if it
   *   doesn't exist.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function createFieldTypeInstance($plugin_id, FormStateInterface $form_state) {
    $field_type_manager = $this->extraFieldTypeManager;

    if (!$field_type_manager->hasDefinition($plugin_id)) {
      return FALSE;
    }

    return $field_type_manager->createInstance(
      $plugin_id,
      $this->getEntityFormStateValue('field_type_config', $form_state, [])
    );
  }

  /**
   * Flush all caches related to this form.
   */
  protected function flushAllCaches() {
    /** @var \Drupal\entity_extra_field\Entity\EntityExtraField $entity */
    $entity = $this->entity;

    $this->cacheDiscovery->delete($entity->getCacheDiscoveryId());

    $this->cacheTagsInvalidator->invalidateTags([
      $entity->getCacheRenderTag()
    ]);

    return $this;
  }

  /**
   * Get extra field type options.
   *
   * @return array
   *   An array of extra field type options.
   */
  protected function getExtraFieldTypeOptions() {
    $options = [];

    foreach ($this->extraFieldTypeManager->getDefinitions() as $plugin_id => $definition) {
      if (!isset($definition['label'])) {
        continue;
      }
      $options[$plugin_id] = $definition['label'];
    }

    return $options;
  }

  /**
   * Get the form state value.
   *
   * @param string|array $key
   *   The element key.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state instance.
   * @param null $default
   *   The default value if nothing is found.
   *
   * @return mixed|null
   *   The form value; otherwise FALSE if the value can't be found.
   */
  protected function getEntityFormStateValue($key, FormStateInterface $form_state, $default = NULL) {
    /** @var \Drupal\entity_extra_field\Entity\EntityExtraField $entity */
    $entity = $this->entity;

    $key = !is_array($key) ? [$key] : $key;

    $inputs = [
      $form_state->cleanValues()->getValues(),
    ];

    if ($entity->id() !== NULL) {
      $inputs[] = $entity->toArray();
    }

    foreach ($inputs as $input) {
      $value = NestedArray::getValue($input, $key, $key_exists);

      if (!isset($value) && !$key_exists) {
        continue;
      }

      return $value;
    }

    return $default;
  }
}
