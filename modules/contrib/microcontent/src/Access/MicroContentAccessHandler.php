<?php

namespace Drupal\microcontent\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines a class for micro-content entity access.
 */
class MicroContentAccessHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $microcontent, $operation, AccountInterface $account) {
    /** @var \Drupal\microcontent\Entity\MicroContentInterface $microcontent */
    // Check if published or can view unpublished micro-content.
    if ($operation === 'view') {
      return AccessResult::allowedIfHasPermission($account,
        'view unpublished microcontent')
        ->cachePerPermissions()
        ->orIf(AccessResult::allowedIf($microcontent->isPublished())
          ->addCacheableDependency($microcontent)
        );
    }

    $any_permission = sprintf('update any %s microcontent', $microcontent->bundle());
    $own_permission = sprintf('update own %s microcontent', $microcontent->bundle());
    return AccessResult::allowedIfHasPermission($account, $any_permission)
      ->orIf(AccessResult::allowedIf($account->hasPermission($own_permission) && $microcontent->getRevisionUserId() === $account->id())
        ->cachePerPermissions()
        ->cachePerUser());
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIf($account->hasPermission('create ' . $entity_bundle . ' microcontent'))->cachePerPermissions();
  }

}
