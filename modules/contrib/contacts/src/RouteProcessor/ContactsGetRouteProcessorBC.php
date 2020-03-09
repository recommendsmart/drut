<?php

namespace Drupal\contacts\RouteProcessor;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\RouteProcessor\OutboundRouteProcessorInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Symfony\Component\Routing\Route;

/**
 * Handles URL generation for backwards compatible dashboard routes.
 *
 * @package Drupal\contacts\RouteProcessor
 */
class ContactsGetRouteProcessorBC implements OutboundRouteProcessorInterface {

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * Constructs a ContactsGetRouteProcessorBC  object.
   *
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   */
  public function __construct(RouteProviderInterface $route_provider) {
    $this->routeProvider = $route_provider;
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($route_name, Route $route, array &$parameters, BubbleableMetadata $bubbleable_metadata = NULL) {
    if ($bc_route = $route->getOption('_contacts_bc_route')) {
      // Suppress coder error on trigger_error message patterns.
      //@codingStandardsIgnoreStart
      @trigger_error(sprintf("The '%s' route is deprecated and will be removed in 8.2.0. Use the '%s' route instead.", $route_name, $bc_route), E_USER_DEPRECATED);
      //@codingStandardsIgnoreEnd
      static::overwriteRoute($route, $this->routeProvider->getRouteByName($bc_route));
    }
  }

  /**
   * Overwrites one route's metadata with the other's.
   *
   * @param \Symfony\Component\Routing\Route $target_route
   *   The route whose metadata to overwrite.
   * @param \Symfony\Component\Routing\Route $source_route
   *   The route whose metadata to read from.
   *
   * @see \Symfony\Component\Routing\Route
   */
  protected static function overwriteRoute(Route $target_route, Route $source_route) {
    $target_route->setPath($source_route->getPath());
    $target_route->setDefaults($source_route->getDefaults());
    $target_route->setRequirements($source_route->getRequirements());
    $target_route->setOptions($source_route->getOptions());
    $target_route->setHost($source_route->getHost());
    $target_route->setSchemes($source_route->getSchemes());
    $target_route->setMethods($source_route->getMethods());
  }

}
