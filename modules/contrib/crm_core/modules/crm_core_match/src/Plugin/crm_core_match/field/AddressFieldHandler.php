<?php

namespace Drupal\crm_core_match\Plugin\crm_core_match\field;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class for evaluating address field fields.
 *
 * Implementation of FieldHandlerInterface for address field.
 */
class AddressFieldHandler extends FieldHandlerBase {

  use StringTranslationTrait;

  /**
   * This function is going to add all addressfield components.
   *
   * @see DefaultMatchingEngineFieldType::fieldRender()
   */
  public function fieldRender($field, $field_info, &$form) {
    foreach ($field_info['columns'] as $item => $info) {
      // This separation is rather logical than formal  at the moment.
      $text_items = [
        'name_line',
        'first_name',
        'last_name',
        'organisation_name',
        'administrative_area',
        'sub_administrative_area',
        'locality',
        'dependent_locality',
        'thoroughfare',
        'premise',
        'sub_premise',
      ];
      $select_items = [
        'country',
        'postal_code',
      ];

      $field_item['field_name'] = $field['field_name'];
      $field_item['label'] = $field['label'] . ': ' . $info['description'];
      $field_item['bundle'] = $field['bundle'];
      $field_item['field_item'] = $item;

      if (in_array($item, $select_items)) {
        $item = new SelectFieldHandler();
        $item->fieldRender($field_item, $field_info, $form);
      }
      if (in_array($item, $text_items)) {
        $item = new textMatchField();
        $item->fieldRender($field_item, $field_info, $form);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getOperators($property = 'value') {
    return [
      'equals' => $this->t('Equals'),
    ];
  }

}
