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
    $options_id_type = [
      "01" => $this->t("Physical person id"),
      "02" => $this->t("Company id"),
      "03" => $this->t("DIMEX"),
      "04" => $this->t("NITE"),
    ];
    $settings = \Drupal::config('e_invoice_cr.settings');
    // Get default values.
    $environment = $settings->get('environment');
    $username = $settings->get('username');
    $password = $settings->get('password');
    $id_type = $settings->get('id_type');
    $id = $settings->get('id');
    $name = $settings->get('name');
    $email = $settings->get('email');
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

    $form['settings_tab']['stuff']['auth_group']['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Password:'),
      '#default_value' => $password,
      '#required' => TRUE,
    ];

    $form['settings_tab']['stuff']['taxpayer_group'] = [
      '#type' => 'details',
      '#title' => $this->t('Taxpayer information.'),
      '#collapsed' => FALSE,
    ];

    $form['settings_tab']['stuff']['taxpayer_group']['id_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Id type.'),
      '#default_value' => $id_type,
      '#required' => TRUE,
      '#options' => $options_id_type,
      '#description' => $this->t("Select the taxpayer's id type."),
      '#validated' => TRUE,
    ];
    $form['settings_tab']['stuff']['taxpayer_group']['id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Id number:'),
      '#default_value' => $id,
      '#required' => TRUE,
      '#size' => 12,
      '#maxlength' => 12,
    ];
    $form['settings_tab']['stuff']['taxpayer_group']['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name:'),
      '#default_value' => $name,
      '#required' => TRUE,
    ];
    $form['settings_tab']['stuff']['taxpayer_group']['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email:'),
      '#default_value' => $email,
      '#required' => TRUE,
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
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $tabs = $values['settings_tab']['stuff'];
    $id_type = $tabs['taxpayer_group']['id_type'];
    switch ($id_type) {
      case "01":
        if (strlen($tabs['taxpayer_group']['id']) !== 9) {
          $form_state->setErrorByName('id', $this->t("The id should have 9 characters, add zeros at the start if it's necessary."));
        }
        break;

      case "02":
        if (strlen($tabs['taxpayer_group']['id']) !== 10) {
          $form_state->setErrorByName('id', $this->t("The id should have 10 characters, add zeros at the start if it's necessary."));
        }
        break;

      case "03":
        if (strlen($tabs['taxpayer_group']['id']) < 11 || strlen($tabs['taxpayer_group']['id']) > 12) {
          $form_state->setErrorByName('id', $this->t("The id should have 11 or 12 characters, add zeros at the start if it's necessary."));
        }
        break;

      case "04":
        if (strlen($form_state->getValue('id')) !== 10) {
          $form_state->setErrorByName('id', $this->t("The id should have 10 characters, add zeros at the start if it's necessary."));
        }
        break;

    }
    if (!is_numeric($tabs['taxpayer_group']['id'])) {
      $form_state->setErrorByName('id', $this->t('This field should only have numbers. No spaces or special characters.'));
    }

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
      ->set('username', $tabs['auth_group']['username'])
      ->set('password', $tabs['auth_group']['password'])
      ->set('id_type', $tabs['taxpayer_group']['id_type'])
      ->set('id', $tabs['taxpayer_group']['id'])
      ->set('name', $tabs['taxpayer_group']['name'])
      ->set('email', $tabs['taxpayer_group']['email'])
      ->set('invoice_logo_file', $tabs['email_text_group']['invoice_logo_file'])
      ->save('file', $tabs['email_text_group']['invoice_logo_file']);


    parent::submitForm($form, $form_state);
  }

  /**
   * Function to store all the settings files.
   */
  
    /** @var \Drupal\file\Entity\File $file */
    

  }
