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
    $base_url = $host = \Drupal::request()->getHost();
    $options_env = [
      "1" => $this->t("Production"),
      "2" => $this->t("Sandbox"),
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
      '#description' => $this->t('Select "Production" to set the production mode or "Sandbox" to set the tests mode.'),
      '#validated' => TRUE,
    ];
    $form['settings_tab'] = [
      '#type' => 'horizontal_tabs',
      '#tree' => TRUE,
      '#prefix' => '<div id="settings-invoice-wrapper">',
      '#suffix' => '</div>',
    ];
    $form['settings_tab']['stuff']['email_text_group'] = [
      '#type' => 'details',
      '#title' => $this->t('Email notifications.'),
      '#collapsed' => FALSE,
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
