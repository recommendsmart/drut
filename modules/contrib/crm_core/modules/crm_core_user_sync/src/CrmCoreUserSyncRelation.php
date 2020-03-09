<?php

namespace Drupal\crm_core_user_sync;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\crm_core_contact\IndividualInterface;
use Drupal\crm_core_user_sync\Entity\Relation;
use Drupal\user\UserInterface;

/**
 * Relation service.
 *
 * @package Drupal\crm_core_user_sync
 */
class CrmCoreUserSyncRelation implements CrmCoreUserSyncRelationInterface {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Relation Storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $relationStorage;

  /**
   * Entity Storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $individualStorage;

  /**
   * Relation rules service.
   *
   * @var \Drupal\crm_core_user_sync\CrmCoreUserSyncRelationRules
   */
  protected $rules;

  /**
   * Logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs a CrmCoreUserSyncRelation object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\crm_core_user_sync\CrmCoreUserSyncRelationRules $rules
   *   Relation rules service.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   Logger channel.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, CrmCoreUserSyncRelationRules $rules, LoggerChannelInterface $logger) {
    $this->entityTypeManager = $entity_type_manager;
    $this->relationStorage = $entity_type_manager->getStorage('crm_core_user_sync_relation');
    $this->individualStorage = $entity_type_manager->getStorage('crm_core_individual');
    $this->rules = $rules;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function getIndividualIdFromUserId($user_id) {
    $individual_id = NULL;

    $rids = $this->relationStorage->getQuery()
      ->condition('user_id', $user_id)
      ->range(0, 1)
      ->execute();

    if (!empty($rids)) {
      $relation_id = reset($rids);
      /* @var $relation \Drupal\crm_core_user_sync\Entity\Relation */
      $relation = $this->relationStorage->load($relation_id);
      $individual_id = $relation->getIndividualId();
    }

    return $individual_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserIdFromIndividualId($individual_id) {
    $user_id = NULL;

    $rids = $this->relationStorage->getQuery()
      ->condition('individual_id', $individual_id)
      ->range(0, 1)
      ->execute();

    if (!empty($rids)) {
      $relation_id = reset($rids);
      /* @var $relation \Drupal\crm_core_user_sync\Entity\Relation */
      $relation = $this->relationStorage->load($relation_id);
      $user_id = $relation->getUserId();
    }

    return $user_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getRelationIdFromUserId($user_id) {
    $rids = $this->relationStorage->getQuery()
      ->condition('user_id', $user_id)
      ->range(0, 1)
      ->execute();

    if (!empty($rids)) {
      return reset($rids);
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getRelationIdFromIndividualId($individual_id) {
    $rids = $this->relationStorage->getQuery()
      ->condition('individual_id', $individual_id)
      ->range(0, 1)
      ->execute();

    if (!empty($rids)) {
      return reset($rids);
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function relate(UserInterface $account, IndividualInterface $contact = NULL) {
    // No contact and $account->crm_core_no_auto_sync => no sync.
    if (empty($contact) && !empty($account->crm_core_no_auto_sync)) {
      return NULL;
    }

    if (empty($contact)) {
      if ($this->getIndividualIdFromUserId($account->id())) {
        // Account already has related contact.
        return NULL;
      }

      $contact_type = $this->rules->getContactType($account);
      if (!$contact_type) {
        // No rules configured on this type.
        return NULL;
      }

      /** @var \Drupal\crm_core_contact\Entity\IndividualType $type */
      $type = $this->entityTypeManager
        ->getStorage('crm_core_individual_type')
        ->load($contact_type);
      $fields = $type->getPrimaryFields();

      $config = \Drupal::config('crm_core_user_sync.settings');
      if ($config->get('auto_sync_user_relate') && isset($fields['email']) && !empty($fields['email'])) {
        $matches = $this->individualStorage->loadByProperties([
          $fields['email'] => $account->getEmail(),
          'type' => $contact_type,
        ]);
        if (count($matches) === 1) {
          $contact = reset($matches);
        }
      }

      if (empty($contact)) {
        $contact = $this->individualStorage->create(['type' => $contact_type]);
        $contact->setOwner($account);
        // For now we just add the name.
        $contact->name->given = $account->getAccountName();

        if (isset($fields['email']) && !empty($fields['email'])) {
          $contact->set($fields['email'], $account->getEmail());
        }
        $contact->save();
      }
    }

    // Check if contact can be synchronized to a contact.
    if (!$this->rules->valid($account, $contact)) {
      return NULL;
    }

    // Check if crm_core_user_sync relation exists for any of endpoint.
    if ($this->getIndividualIdFromUserId($account->id()) ||
      $this->getUserIdFromIndividualId($contact->id())) {
      return NULL;
    }

    $relation = Relation::create();
    $relation->setUser($account);
    $relation->setIndividual($contact);
    $relation->save();

    $this->logger->notice('User @user @uid has been synchronized to the contact @contact_id, relation @rid has been created.', [
      '@user' => $account->getDisplayName(),
      '@uid' => $account->id(),
      '@contact_id' => $contact->id(),
      '@rid' => $relation->id(),
    ]);

    return $contact;
  }

}
