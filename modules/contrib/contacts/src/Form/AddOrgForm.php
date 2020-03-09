<?php

namespace Drupal\contacts\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * The Add Organisation form.
 */
class AddOrgForm extends AddContactBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'contacts_add_org_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $profile_fields = $this->entityFieldManager->getFieldDefinitions('profile', 'crm_org');
    $profile_fields['crm_org_name']->setRequired(TRUE);
    $profile = $this->entityTypeManager->getStorage('profile')->create(['type' => 'crm_org']);
    $form['crm_org_name'] = $this->getWidgetForm($profile, $profile_fields, 'crm_org_name', $form, $form_state);
    $form['crm_org_name']['#weight'] = 0;
    $form['crm_org_name']['#entity_namespace'] = 'profile';

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Add organisation'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntities(array $form, FormStateInterface $form_state) {
    parent::buildEntities($form, $form_state);

    /* @var \Drupal\profile\Entity\ProfileInterface $profile */
    $profile = $this->entityTypeManager->getStorage('profile')->create([
      'type' => 'crm_org',
      'status' => TRUE,
      'is_default' => TRUE,
    ]);
    $profile->setValidationRequired(!$form_state->getTemporaryValue('entity_validated'));
    $profile_fields = $this->entityFieldManager->getFieldDefinitions('profile', 'crm_org');

    foreach (Element::children($form) as $field_name) {
      if ('profile' !== ($form[$field_name]['#entity_namespace'] ?? NULL)) {
        continue;
      }
      $widget = $this->getWidget($profile_fields, $field_name);
      $widget->extractFormValues($profile->get($field_name), $form, $form_state);
    }

    $this->profile = $profile;

    // Add our relevant role.
    $this->user->addRole('crm_org');
  }

}
