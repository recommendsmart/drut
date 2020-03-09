<?php

namespace Drupal\contacts_log;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\profile\Entity\ProfileInterface;

/**
 * Profile logger service.
 */
class ProfileLogger {

  /**
   * The message storage.
   *
   * @var \Drupal\Core\Entity\ContentEntityStorageInterface
   */
  protected $storage;

  /**
   * An array of bundles to exclude from logging.
   *
   * @var string[]
   */
  protected $excludedBundles = [];

  /**
   * Constructs the profile logger.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->storage = $entity_type_manager->getStorage('message');
  }

  /**
   * Add bundles to exclude from logging.
   *
   * @param string[] $bundles
   *   The bundles to exclude.
   *
   * @return $this
   */
  public function addExcludedBundles(array $bundles) {
    $this->excludedBundles = array_merge($this->excludedBundles, $bundles);
    return $this;
  }

  /**
   * Check whether a certain profile should be excluded from logging.
   *
   * @param \Drupal\profile\Entity\ProfileInterface $profile
   *   The profile.
   *
   * @return bool
   *   Whether the profile should be excluded.
   */
  protected function exclude(ProfileInterface $profile): bool {
    // Allow a value on the profile to indicate exclusions, for example for
    // migrations.
    if (!empty($profile->contacts_log_exclude)) {
      return TRUE;
    }

    // Allow excluding certain profile types.
    if (in_array($profile->bundle(), $this->excludedBundles)) {
      return TRUE;
    }

    // Don't log on non-existent, new or anonymous users.
    $owner = $profile->getOwner();
    return !$owner || $owner->isNew() || $owner->isAnonymous();
  }

  /**
   * React to inserting a profile.
   *
   * @param \Drupal\profile\Entity\ProfileInterface $profile
   *   The profile being inserted.
   */
  public function onInsert(ProfileInterface $profile): void {
    if ($this->exclude($profile)) {
      return;
    }

    // Allow the owner to be set automatically but set contact to attached user.
    $message = $this->storage->create([
      'template' => 'contacts_log_create_profile',
      'contact' => $profile->getOwnerId(),
    ]);
    $message->set('profile', $profile);
    $message->save();
  }

  /**
   * React to updating a profile.
   *
   * @param \Drupal\profile\Entity\ProfileInterface $profile
   *   The profile being updated.
   */
  public function onUpdate(ProfileInterface $profile): void {
    if ($this->exclude($profile)) {
      return;
    }

    // Allow the owner to be set automatically but set contact to attached user.
    $message = $this->storage->create([
      'template' => 'contacts_log_update_profile',
      'contact' => $profile->getOwnerId(),
    ]);
    $message->set('profile', $profile);
    $message->save();
  }

}
