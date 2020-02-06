<?php

namespace Drupal\config_actions;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages configuration packaging methods.
 */
class ConfigActionsSourceManager extends DefaultPluginManager {

  /**
   * Constructs a new ConfigActionsSource object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   An object that implements CacheBackendInterface.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   An object that implements ModuleHandlerInterface.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/ConfigActionsSource', $namespaces, $module_handler,
      'Drupal\config_actions\ConfigActionsSourceInterface', 'Drupal\config_actions\Annotation\ConfigActionsSource');
    $this->alterInfo('config_actions');
    $this->setCacheBackend($cache_backend, 'config_actions_source');
  }

}
