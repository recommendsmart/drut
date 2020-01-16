<?php

namespace Drupal\assembly\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Assembly type entities.
 */
interface AssemblyTypeInterface extends ConfigEntityInterface {

  /**
   * Icon upload location.
   *
   * @var string
   */
  const ICON_UPLOAD_LOCATION = 'temporary://';

  // Add get/set methods for your configuration properties here.
}
