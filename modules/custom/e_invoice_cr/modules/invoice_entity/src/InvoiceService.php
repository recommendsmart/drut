<?php

namespace Drupal\invoice_entity;

use Drupal\invoice_entity\Entity\InvoiceEntity;
use Drupal\invoice_entity\Entity\InvoiceEntityInterface;
use Drupal\invoice_email\InvoiceEmailEvent;
use Drupal\invoice_received_entity\Entity\InvoiceReceivedEntity;
use Drupal\invoice_received_entity\Entity\InvoiceReceivedEntityInterface;

/**
 * Class InvoiceService.
 */
class InvoiceService implements InvoiceServiceInterface {

  protected static $invoiceNumber;
  protected static $secureCode;
  protected static $consecutiveName;

  /**
   * Constructs a new InvoiceService object.
   */
  public function __construct() {
    // It gets a random number.
    self::$secureCode = str_pad(intval(rand(1, 99999999)), 8, '0', STR_PAD_LEFT);
  }

  /**
   * Call the validateDocument from Communication and return its result.
   *
   * @param string $key
   *   Key to eval.
   *
   * @return array|null|string
   *   Return the response from the api.
   */
 

  /**
   * Increase the current values by one.
   */
  public function increaseValues() {
    self::$invoiceNumber = str_pad(intval(self::$invoiceNumber) + 1, 10, '0', STR_PAD_LEFT);
    self::$secureCode = str_pad(intval(rand(1, 99999999)), 8, '0', STR_PAD_LEFT);
  }

  /**
   * Decrease the current values by one.
   */
  public function decreaseValues() {
    self::$invoiceNumber = str_pad(intval(self::$invoiceNumber) - 1, 10, '0', STR_PAD_LEFT);
    self::$secureCode = str_pad(intval(rand(1, 99999999)), 8, '0', STR_PAD_LEFT);
  }

  /**
   * Update the configuration values.
   */
  public function updateValues() {
    $this->setInvoiceVariable(self::$consecutiveName, self::$invoiceNumber);
  }

  /**
   * {@inheritdoc}
   */
  public function checkInvoiceKey($key) {
    $result = $this->responseForKey($key);
    if (is_null($result)) {
      return FALSE;
    }
    else {
      if ($result[2] != 'aceptado') {
        $messages = explode("\n-", $result[3]->DetalleMensaje);
        $messages = array_filter($messages, function ($val) {
          $code = substr($val, 0, 2);
          return $code == '29' || $code == '99';
        });

        return !empty($messages);
      }
      return TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  
  /**
   * {@inheritdoc}
   */
  public function validateInvoiceReceivedEntity(InvoiceReceivedEntity $entity) {
    $key = $entity->get('document_key')->value;
    $result = $this->responseForKey($key);
    $status = $entity->get('field_ir_status')->value;
    if (!is_null($result)) {
      $status = $result[2] === 'aceptado' ?
        InvoiceReceivedEntity::IR_ACCEPTED_STATUS : InvoiceReceivedEntity::IR_REJECTED_STATUS;
      $entity->set('field_ir_status', $status);
      $entity->save();
    }
    return [
      'state' => $status,
      'response' => $result,
    ];
  }

  /**
   * {@inheritdoc}
   */

  /**
   * {@inheritdoc}
   */
  public function generateConsecutive($type) {
    $document_code = isset(InvoiceEntityInterface::DOCUMENTATION_INFO[$type]) ?
      InvoiceEntityInterface::DOCUMENTATION_INFO[$type]['code'] : '01';

    return $this->generateConsecutiveDoc($document_code);
  }

  /**
   * {@inheritdoc}
   */
  public function generateMessageConsecutive($code) {
    $document_code = InvoiceReceivedEntityInterface::IR_MESSAGES_STATES[$code]['code'];

    return $this->generateConsecutiveDoc($document_code);
  }

  /**
   * {@inheritdoc}
   */
  private function generateConsecutiveDoc($code) {
    return '00100001' . $code . self::$invoiceNumber;
  }

  /**
   * {@inheritdoc}
   */

  /**
   * {@inheritdoc}
   */
  public static function setInvoiceVariable($variable_name, $value) {
    $config = \Drupal::service('config.factory')->getEditable('invoice_entity.settings');
    $config->set($variable_name, $value)->save();
  }

  /**
   * {@inheritdoc}
   */
  public function getInvoiceVariable($variable_name) {
    $config = \Drupal::config('invoice_entity.settings');
    $value = $config->get($variable_name);
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getDocumentNumber() {
    return self::$invoiceNumber;
  }

  /**
   * {@inheritdoc}
   */
  public function setConsecutiveNumber($documentType) {

    switch ($documentType) {
      case 'FE':
        self::$consecutiveName = 'electronic_bill_consecutive';
        break;

      case 'ND':
        self::$consecutiveName = 'debit_note_consecutive';
        break;

      case 'NC':
        self::$consecutiveName = 'credit_note_consecutive';
        break;

      case 'TE':
        self::$consecutiveName = 'electronic_ticket_consecutive';
        break;

      case '1':
        self::$consecutiveName = 'invoice_accepted_consecutive';
        break;

      case '2':
        self::$consecutiveName = 'invoice_partial_accepted_consecutive';
        break;

      case '3':
        self::$consecutiveName = 'invoice_rejected_consecutive';
        break;

      default:
        self::$consecutiveName = 'electronic_bill_consecutive';
        break;
    }

    self::$invoiceNumber = $this->getInvoiceVariable(self::$consecutiveName);
    if (is_null(self::$invoiceNumber)) {
      self::$invoiceNumber = '0000000001';
      $this->updateValues();
    }
  }

  /**
   * {@inheritdoc}
   */

}
