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
    $username = $settings->get('username');
    $logo_file = $settings->get('invoice_logo_file');


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

    $form['settings_tab']['stuff']['auth_group'] = [
      '#type' => 'details',
      '#title' => $this->t('API login information.'),
      '#description' => t('This module does the API login through the Oauth 2.0 token.'),
      '#collapsed' => FALSE,
    ];

    $form['settings_tab']['stuff']['auth_group']['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username:'),
      '#default_value' => $username,
      '#required' => TRUE,
    ];
    $form['settings_tab']['stuff']['taxpayer_group'] = [
      '#type' => 'details',
      '#title' => $this->t('Taxpayer information.'),
      '#collapsed' => FALSE,
    ];
    $form['settings_tab']['stuff']['email_text_group'] = [
      '#type' => 'details',
      '#title' => $this->t('Email notifications.'),
      '#collapsed' => FALSE,
    ];

    $form['settings_tab']['stuff']['email_text_group']['invoice_logo_file'] = [
      '#title' => $this->t('Company Logo'),
      '#type' => 'managed_file',
      '#description' => $this->t('Add a company logo that it will be print on the invoice documents.'),
      '#upload_validators' => [
        'file_validate_extensions' => ['png jpg jpeg'],
        'file_validate_image_resolution' => ["300x300", ""],
      ],
      '#default_value' => $logo_file,
      '#theme' => 'image_widget',
      '#preview_image_style' => 'medium',
      '#upload_location' => 'public://',
      '#required' => FALSE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */

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
      ->set('username', $tabs['auth_group']['username'])
      ->set('invoice_logo_file', $tabs['email_text_group']['invoice_logo_file'])
      ->save('file', $tabs['email_text_group']['invoice_logo_file']);


    parent::submitForm($form, $form_state);
  }

  /**
   * Function to store all the settings files.
   */
  
    /** @var \Drupal\file\Entity\File $file */
    

  }
