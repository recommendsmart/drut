<?php

namespace Drupal\openbusiness_content_creator\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns a simple page.
 *
 * @return array
 *   Returns a renderable array
 */
class HomeController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function page() {

    /* Generates an empty page. */
    return [
      '#title' => 'Home',
    ];
  }

}
