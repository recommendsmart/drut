<?php

namespace Drupal\Tests\microcontent\Traits;

use Drupal\microcontent\Entity\MicroContent;
use Drupal\microcontent\Entity\MicroContentInterface;
use Drupal\microcontent\Entity\MicroContentType;
use Drupal\microcontent\Entity\MicroContentTypeInterface;

/**
 * Defines a micro-content test trait.
 */
trait MicroContentTestTrait {

  /**
   * Creates a micro-content type.
   *
   * @param string $id
   *   Type ID.
   * @param string $name
   *   Type name.
   *
   * @return \Drupal\microcontent\Entity\MicroContentTypeInterface
   *   New micro-content type.
   */
  protected function createMicroContentType(string $id, string $name) : MicroContentTypeInterface {
    $type = MicroContentType::create([
      'id' => $id,
      'name' => $name,
    ]);
    $type->save();
    if (method_exists($this, 'markEntityForCleanup')) {
      $this->markEntityForCleanup($type);
    }
    return $type;
  }

  /**
   * Creates micro-content.
   *
   * @param array $values
   *   Field values.
   *
   * @return \Drupal\microcontent\Entity\MicroContentInterface
   *   New micro-content entity.
   */
  protected function createMicroContent(array $values) : MicroContentInterface {
    $entity = MicroContent::create($values);
    $entity->save();
    if (method_exists($this, 'markEntityForCleanup')) {
      $this->markEntityForCleanup($entity);
    }
    return $entity;
  }

}
