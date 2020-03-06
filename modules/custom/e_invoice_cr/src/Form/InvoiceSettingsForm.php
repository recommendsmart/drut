<?php

namespace Drupal\e_invoice_cr\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

/**
 * Configure e_invoice settings for this site.
 */
class InvoiceSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'e_invoice_cr_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'e_invoice_cr.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $options_env = [
      "1" => $this->t("Production"),
    ];
    $settings = \Drupal::config('e_invoice_cr.settings');
    // Get default values.
    $environment = $settings->get('environment');

    $form['environment'] = [
      '#type' => 'select',
      '#title' => $this->t('Select the environment.'),
      '#default_value' => $environment,
      '#required' => TRUE,
      '#options' => $options_env,
      '#description' => $this->t('Select "Production" to set the production mode.'),
      '#validated' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $tabs = $values['settings_tab']['stuff'];
    // Retrieve the configuration.
    \Drupal::configFactory()->getEditable('e_invoice_cr.settings')
      // Set the submitted configuration setting.
      ->set('environment', $form_state->getValue('environment'))

    parent::submitForm($form, $form_state);
  } 
}
