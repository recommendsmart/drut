<?php

namespace Drupal\microcontent\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class for adding and editing micro-content.
 */
class MicroContentForm extends ContentEntityForm {

  /**
   * Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('logger.factory')->get('microcontent'),
      $container->get('messenger')
    );
  }

  /**
   * Constructs a MicroContentTypeForm.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messenger.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info, TimeInterface $time,LoggerInterface $logger, MessengerInterface $messenger) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
    $this->logger = $logger;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $microcontent = $this->entity;
    $status = $microcontent->save();

    $edit_link = $this->entity->toLink($this->t('Edit'), 'edit-form')->toString();
    $form_state->setRedirectUrl($microcontent->toUrl('collection'));
    if ($status == SAVED_UPDATED) {
      $this->messenger()->addStatus($this->t('Micro-content %label has been updated.', ['%label' => $microcontent->label()]));
      $this->logger->notice('Micro-content %label has been updated.', [
        '%label' => $microcontent->label(),
        'link' => $edit_link,
      ]);
      return $status;
    }
    $this->messenger()->addStatus($this->t('Micro-content %label has been added.', ['%label' => $microcontent->label()]));
    $this->logger->notice('Micro-content %label has been added.', [
      '%label' => $microcontent->label(),
      'link' => $edit_link,
    ]);
    return $status;
  }

}
