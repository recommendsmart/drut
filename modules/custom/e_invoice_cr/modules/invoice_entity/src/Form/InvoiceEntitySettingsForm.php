<?php

namespace Drupal\invoice_entity\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\invoice_entity\InvoiceService;

/**
 * Class InvoiceEntitySettingsForm.
 *
 * @ingroup invoice_entity
 */
class InvoiceEntitySettingsForm extends FormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'invoiceentity_settings';
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Empty implementation of the abstract submit class.
    $value = $form_state->getValue('consecutive_number');
    $value = str_pad($value, 10, '0', STR_PAD_LEFT);
    InvoiceService::setInvoiceVariable('invoice_number', $value);
    drupal_set_message($this->t('Current invoice number updated.'));
  }

  /**
   * Defines the settings form for Invoice entities.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\invoice_entity\InvoiceService $invoice_service */
    $invoice_service = \Drupal::service('invoice_entity.service');

    $form['invoiceentity_settings']['#markup'] = 'Settings form for Invoice entities. Manage field settings here.';

    $form['consecutive_number'] = [
      '#type' => 'number',
      '#description' => t('The number of the next invoice.'),
      '#default_value' => intval($invoice_service->getInvoiceVariable('invoice_number')),
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save configuration'),
      '#button_type' => 'primary',
    ];
    $form['#theme'] = 'system_config_form';

    return $form;
  }

}
