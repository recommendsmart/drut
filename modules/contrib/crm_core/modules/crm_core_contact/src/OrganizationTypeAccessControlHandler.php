<?php

namespace Drupal\crm_core_contact;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control handler for CRM Core Organization type entities.
 */
class OrganizationTypeAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\crm_core_contact\Entity\OrganizationType $entity */

    // First check permission.
    if (parent::checkAccess($entity, $operation, $account)->isForbidden()) {
      return AccessResult::forbidden();
    }

    switch ($operation) {
      case 'delete':
        // If organization instance of this organization type exist, you can't
        // delete it.
        $results = \Drupal::entityQuery('crm_core_organization')
          ->condition('type', $entity->id())
          ->execute();
        return AccessResult::allowedIf(empty($results));

      case 'view':
      case 'edit':
      case 'update':
        // If the organization type is locked, you can't edit it.
        return AccessResult::allowed();
    }
  }

}
