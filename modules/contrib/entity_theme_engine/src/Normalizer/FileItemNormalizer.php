<?php

namespace Drupal\entity_theme_engine\Normalizer;


class FileItemNormalizer extends FieldItemNormalizer {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = ['Drupal\file\Plugin\Field\FieldType\FileItem'];

  /**
   * {@inheritdoc}
   */
  public function normalize($field, $format = NULL, array $context = []) {
    $data = parent::normalize($field, $format, $context);
    $data['file_url'] = file_create_url($field->entity->getFileUri());
    return $data;
  }
}
