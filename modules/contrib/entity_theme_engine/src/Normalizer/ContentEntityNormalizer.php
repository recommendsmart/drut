<?php

namespace Drupal\entity_theme_engine\Normalizer;

use Drupal\serialization\Normalizer\NormalizerBase;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\file\Entity\File;
use Drupal\Core\Cache\Cache;

class ContentEntityNormalizer extends NormalizerBase {

  /**
   * @var string[]
   */
  protected $supportedInterfaceOrClass = ['Drupal\Core\Entity\ContentEntityInterface'];

  /**
   * @var string[]
   */
  protected $format = ['twig_variable'];

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;
  
  /**
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, array $context = []) {
    $data = [
      'entity_id' => $entity->id(),
      'entity_uuid' => $entity->uuid(),
      'entity_label' => $entity->label(),
      'entity_url' => ($entity->id() && $entity->hasLinkTemplate('canonical'))?$entity->toUrl()->toString():"",
      'entity_type' => $entity->getEntityTypeId(),
    ];
    $_cache = &drupal_static('twig_variables_entity',[]);
    if(!empty($_cache[$entity->getEntityTypeId()][$entity->id()])) {
      return $_cache[$entity->getEntityTypeId()][$entity->id()];
    }
    $_cache[$entity->getEntityTypeId()][$entity->id()] = true;
    $cache = [
      'contexts' => $entity->getCacheContexts()?:[],
      'tags' => $entity->getCacheTags()?:[],
      'max-age' => $entity->getCacheMaxAge(),
    ];
    $data['#cache'] = $cache;
    if($context['level'] > 5) {
      return $data;
    };
    if($entity instanceof FieldableEntityInterface) {
      $fields = $entity->getFieldDefinitions();
      foreach ($fields as $field_name => $field_def) {
        if(array_key_exists($field_name, $data)) continue;
        $field_type = $field_def->getType();
        $sub_context = $context + [
        'field_name' => $field_name,
        'field_type' => $field_type,
        'field_definition' => $field_def
        ];
        if($field_def->getFieldStorageDefinition()->getCardinality() == 1) {
          if($entity->get($field_name)->isEmpty()){
            $data[$field_name] = NULL;
          }else {
            $field_data = $this->serializer->serialize($entity->get($field_name)->get(0), $format, $sub_context);
            if(!empty($field_data['#cache'])) {
              $cache = $this->mergeCache($cache, $field_data['#cache']);
            }
            $data[$field_name] = $field_data;
          }
        }else {
          foreach ($entity->get($field_name) as $item) {
            $field_data = $this->serializer->serialize($item, $format, $sub_context);
            if(!empty($field_data['#cache'])) {
              $cache = $this->mergeCache($cache, $field_data['#cache']);
            }
            $data[$field_name]['items'][] = $field_data;
          }
        }
      }
    }
    $data['#cache'] = $cache;
    $_cache[$entity->getEntityTypeId()][$entity->id()] = $data;
    return $data;
  }
  /**
   * 
   * @param array $a
   * @param array $b
   * @return string[][]|number[]
   */
  public function  mergeCache(array $a, array $b) {
    $cache = [];
    $cache['contexts'] = Cache::mergeContexts($a['contexts']?:[],$b['contexts']?:[]);
    $cache['tags'] = Cache::mergeTags($a['tags']?:[],$b['tags']?:[]);
    $cache['max-age'] = Cache::mergeMaxAges($a['max-age'],$b['max-age']);
    return $cache;
  }
  /**
   * {@inheritdoc}
   */
  public function supportsNormalization($data, $format = NULL) {
    if(in_array($format, $this->format) && parent::supportsNormalization($data, $format)) {
      return TRUE;
    }
    return FALSE;
  }

  public function getImageStylesVariables(File $entity) {
    $variables = [];
    $styles = static::getStyles();
    foreach ($styles as $id => $style) {
      $variables[$id] = $style->buildUrl($entity->getFileUri());
    }
    return $variables;
  }
  public function getStyles() {
    $cache = &drupal_static(__METHOD__, []);
    if(empty($cache)) {
      $cache = $this->entityTypeManager->getStorage('image_style')->loadMultiple();
    }
    return $cache;
  }
}
