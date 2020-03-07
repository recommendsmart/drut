<?php

namespace Drupal\microcontent\EntityHandlers;

use Drupal\views\EntityViewsData;

/**
 * Defines a class for microcontent views data.
 */
class MicrocontentViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();
    $data['microcontent_field_data']['uid']['filter']['id'] = 'user_name';
    $data['microcontent_field_revision']['uid']['filter']['id'] = 'user_name';
    return $data;
  }

}
