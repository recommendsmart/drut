<?php

namespace Drupal\invoice_entity;

use Drupal\invoice_entity\Entity\InvoiceEntity;
use Drupal\invoice_received_entity\Entity\InvoiceReceivedEntity;

/**
 * Interface InvoiceServiceInterface.
 */
interface InvoiceServiceInterface {

  /**
   * Generate and check if the generated key is already used.
   *
   * @param string $type
   *   The type of the invoice.
   * @param bool $received
   *   If the document is a received invoice.
   *
   * @return string
   *   Return the new unique key.
   */
  public function getUniqueInvoiceKey($type = 'FE', $received = FALSE);

  /**
   * Set variable value.
   *
   * @param string $variable_name
   *   Variable machine name.
   * @param string $value
   *   New value for the variable.
   */
  public static function setInvoiceVariable($variable_name, $value);

  /**
   * Gets variables.
   *
   * @return string
   *   Get value of the requested variable.
   */
  public function getInvoiceVariable($variable_name);

}
