<?php

namespace Drupal\contacts;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Build the breadcrumbs on the contacts dashboard.
 */
class BreadcrumbBuilder implements BreadcrumbBuilderInterface {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    return in_array(
      $route_match->getRouteName(),
      [
        'contacts.collection',
        'contacts.contact',
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();
    $links = [];

    $links[] = Link::createFromRoute($this->t('Contacts'), 'contacts.collection');

    if ($route_match->getRouteName() == 'contacts.contact') {
      $user = $route_match->getParameter('user');
      $links[] = Link::createFromRoute(
        $user->label(),
        'contacts.contact',
        [
          'user' => $user->id(),
        ]
      );
    }

    return $breadcrumb->setLinks($links);
  }

}
