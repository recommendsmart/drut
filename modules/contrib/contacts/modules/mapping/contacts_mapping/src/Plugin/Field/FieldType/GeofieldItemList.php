<?php

namespace Drupal\contacts_mapping\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemList;
use Drupal\user\UserInterface;

/**
 * Represents a list of geofield item field values.
 */
class GeofieldItemList extends FieldItemList {

  /**
   * Fetch geolocation data from source fields.
   *
   * @return bool
   *   Whether the entity was updated and needs saving.
   */
  public function updateGeoFromSource() {
    $entity = $this->getEntity();

    if (!$entity instanceof UserInterface) {
      return FALSE;
    }

    // If geolocation is computed, do nothing.
    if ($this->computed) {
      return FALSE;
    }

    /* @var \Drupal\user\UserInterface $entity */
    // @todo Make sources configurable plugins.
    if (in_array('crm_indiv', $entity->getRoles())) {
      $profile = $entity->get('profile_crm_indiv')->entity;
    }
    elseif (in_array('crm_org', $entity->getRoles())) {
      $profile = $entity->get('profile_crm_org')->entity;
    }
    else {
      return FALSE;
    }

    /* @var \Drupal\profile\Entity\ProfileInterface $profile */
    if (!$profile->hasField('geolocation_geocoded')) {
      return FALSE;
    }

    $this->setValue($profile->get('geolocation_geocoded')->getValue());
    return TRUE;
  }

}
