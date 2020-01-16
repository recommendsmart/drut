<?php

namespace Drupal\microcontent\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines an interface for micro-content types.
 */
interface MicroContentTypeInterface extends ConfigEntityInterface {

  /**
   * Gets the type description.
   *
   * @return string
   *   Description.
   */
  public function getDescription() : string;

  /**
   * Gets the type class.
   *
   * @return string
   *   Class to apply to aid content-editors.
   */
  public function getTypeClass() : string;

}
