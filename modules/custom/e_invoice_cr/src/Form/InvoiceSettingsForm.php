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
    $email_text = $settings->get('email_text');
    $email_subject = $settings->get('email_subject');
    $email_copies = $settings->get('email_copies');
    if (is_null($email_text)) {
      $email_text = "Find attached an Electronic Invoice, please do not reply this email.";
    }
    if (is_null($email_subject)) {
      $email_subject = "Electronic invoice issued by @company.";
    }

    $form['environment'] = [
      '#type' => 'select',
      '#title' => $this->t('Select the environment.'),
      '#default_value' => $environment,
      '#required' => TRUE,
      '#options' => $options_env,
      '#description' => $this->t('Select "Production" to set the production mode.'),
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
      '#required' => FALSE,
    ];

    $form['settings_tab']['stuff']['auth_group']['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Password:'),
      '#default_value' => $password,
      '#required' => FALSE,
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
      '#description' => $this->t('Add a company logo.'),
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
    $form['settings_tab']['stuff']['email_text_group']['email_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email subject'),
      '#description' => $this->t("Add a subject text that it will be printed on the email invoice notifications. Use @company to print your company name."),
      '#default_value' => $email_subject,
      '#required' => TRUE,
    ];
    $form['settings_tab']['stuff']['email_text_group']['email_text'] = [
      '#title' => $this->t('Email notifications text'),
      '#type' => 'textarea',
      '#description' => $this->t("Add a text that it will be printed on the email invoice notifications sent to the clients.\nUse @company to print your company name, @invoice_id to print the invoice id, @date to print the invoice date, @hour to print the hour and @url to print the pdf invoice link."),
      '#default_value' => $email_text,
      '#required' => TRUE,
    ];
    $form['settings_tab']['stuff']['email_text_group']['email_copies'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Send always copy to'),
      '#description' => $this->t("Insert email addresses to send always a copy, separate the emails using a comma, example: test@test.com, test2@test2.com."),
      '#default_value' => $email_copies,
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
      ->set('email_text', $tabs['email_text_group']['email_text'])
      ->set('email_subject', $tabs['email_text_group']['email_subject'])
      ->set('email_copies', $tabs['email_text_group']['email_copies'])
      ->save('file', $tabs['email_text_group']['invoice_logo_file']);


    parent::submitForm($form, $form_state);
  }

  /**
   * Function to store all the settings files.
   */
  
    /** @var \Drupal\file\Entity\File $file */
    

  }
