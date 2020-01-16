<?php

namespace Drupal\Tests\microcontent\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\microcontent\Traits\MicroContentTestTrait;

/**
 * Defines a class for testing micro-content entities.
 *
 * @group microcontent
 */
class MicroContentEntityTest extends KernelTestBase {

  use MicroContentTestTrait;
  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'microcontent',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installSchema('system', ['sequences']);
    $this->installEntitySchema('microcontent');
  }

  /**
   * Tests micro-content entity and micro-content type.
   */
  public function testMicroContentEntity() {
    $type = $this->createMicroContentType('pane', 'Pane');
    $entity = $this->createMicroContent([
      'type' => $type->id(),
      'label' => 'New pane',
    ]);
    $entity->save();
    $this->assertEquals('New pane', $entity->label());
    $this->assertEquals('pane', $entity->bundle());
  }

}
