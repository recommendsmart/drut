<?php

namespace Drupal\entity_extra_field;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Entity\Display\EntityDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Define extra field type plugin interface.
 */
interface ExtraFieldTypePluginInterface extends PluginFormInterface, ContainerFactoryPluginInterface, ConfigurablePluginInterface {

  /**
   * Display the extra field plugin label.
   *
   * @return string
   *   Return the extra field plugin label.
   */
  public function label();

  /**
   * Build the render array of the extra field type contents.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity type the extra field is being attached too.
   * @param \Drupal\Core\Entity\Display\EntityDisplayInterface $display
   *   The entity display the extra field is apart of.
   *
   * @return array
   *   The extra field renderable array.
   */
  public function build(EntityInterface $entity, EntityDisplayInterface $display);
}
