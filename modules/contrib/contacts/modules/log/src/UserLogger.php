<?php

namespace Drupal\contacts_log;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\user\UserInterface;

/**
 * User logger service.
 */
class UserLogger {

  /**
   * The message storage.
   *
   * @var \Drupal\Core\Entity\ContentEntityStorageInterface
   */
  protected $storage;

  /**
   * Constructs the message logger.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->storage = $entity_type_manager->getStorage('message');
  }

  /**
   * Check whether a certain user should be excluded from logging.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user.
   *
   * @return bool
   *   Whether the user should be excluded.
   */
  protected function exclude(UserInterface $user): bool {
    // Allow a value on the profile to indicate exclusions, for example for
    // migrations.
    if (!empty($user->contacts_log_exclude)) {
      return TRUE;
    }

    // Don't log unsaved or anonymous users.
    return $user->isNew() || $user->isAnonymous();
  }

  /**
   * React to updating a user.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user being updated.
   */
  public function onUpdate(UserInterface $user): void {
    if ($this->exclude($user)) {
      return;
    }

    // Allow the owner to be set automatically but set contact to attached user.
    $message = $this->storage->create([
      'template' => 'contacts_log_update_user',
      'contact' => $user->id(),
    ]);
    $message->save();
  }

}
