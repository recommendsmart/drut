<?php

namespace Drupal\entity_theme_engine\Normalizer;


use Drupal\file\Entity\File;
use Drupal\system\Entity\Menu;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuActiveTrailInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Core\Menu\InaccessibleMenuLink;
use Drupal\Core\Entity\TranslatableInterface;

class EntityReferenceItemNormalizer extends FieldItemNormalizer {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = [
    'Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem',
  ];

  /**
   * @var \Drupal\Core\Menu\MenuActiveTrailInterface
   */
  protected $menu_active_trail;
  
  /**
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menu_tree;
  
  /**
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, MenuActiveTrailInterface $menu_active_trail, MenuLinkTreeInterface $menu_tree) {
    $this->entityTypeManager = $entityTypeManager;
    $this->menu_active_trail = $menu_active_trail;
    $this->menu_tree = $menu_tree;
  }
  /**
   * {@inheritdoc}
   */
  public function normalize($field, $format = NULL, array $context = []) {
    $data = parent::normalize($field, $format, $context);
    if(empty($field->entity)) {
      return $data;
    }
    $entity = $field->entity;
    $_cache = &drupal_static('twig_variables_entity',[]);
    if(!empty($_cache[$entity->getEntityTypeId()][$entity->id()])) {
      return $_cache[$entity->getEntityTypeId()][$entity->id()];
    }
    // Set the entity in the correct language for display.
    if ($entity instanceof TranslatableInterface) {
      $entity = \Drupal::service('entity.repository')->getTranslationFromContext($entity);
    }
    $sub_context = [
      'entity_widget' => $context['entity_widget'],
      'entity' => $entity,
      'attach_mode' => $context['attach_mode'],
      'level' => $context['level'] + 1,
    ];
    if($variables = $this->serializer->serialize($entity, $format, $sub_context)) {
      $data = array_merge($data, $variables);
    }
    if(empty($context['attach_mode']) && !empty($entity)) {
      try {
        $data['render'] = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId())->view($entity);
      } catch (\Exception $e) {
      }
    }
    $field_definition = $context['field_definition'];
    $settings = $field_definition->getFieldStorageDefinition()->getSettings();
    if(!empty($settings['target_type'])) {
      $target_type = $settings['target_type'];
      switch ($target_type) {
        case 'media':
          $media_source = $entity->getSource()->getSourceFieldValue($entity);
          $file = $this->entityTypeManager->getStorage('file')->load($media_source);
          if($file && $file instanceof File) {
            if($entity->bundle() == 'image') {
              $data['styles'] = $this->getImageStylesVariables($file);
            }
            $media_source = file_create_url($file->getFileUri());
          }
          $data['media_source'] = $media_source;
          break;
        case 'image':
          $data['styles'] = $this->getImageStylesVariables($entity);
          break;
        case 'menu':
          $data['data'] = $this->getMenuData($entity);
          $data['#cache']['contexts'][] = 'user.permissions';
          $data['#cache']['contexts'][] = 'route.menu_active_trails:' . $entity->id();
          $data['#cache']['tags'][] = 'config:system.menu.' . $entity->id();
          break;
        case 'taxonomy_vocabulary':
          $data['data'] = $this->getVocabularyData($entity);
          break;
      }
    }
    return $data;
  }
  /**
   * 
   * @param Vocabulary $vocabulary
   * @return array
   */
  public function getVocabularyData(Vocabulary $vocabulary) {
    return [];
  }
  /**
   * 
   * @param Menu $menu
   * @return array
   */
  public function getMenuData(Menu $menu) {
    $parameters = new MenuTreeParameters();
    $parameters->onlyEnabledLinks();
    
    $menu_active_trail = $this->menu_active_trail->getActiveTrailIds($menu->id());
    $parameters->setActiveTrail($menu_active_trail);
    
    $links = $this->menu_tree->load($menu->id(), $parameters);
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkNodeAccess'],
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    $links = $this->menu_tree->transform($links, $manipulators);
    $data = static::processMenuLink($links);
    return $data;
  }
  /**
   * 
   * @param array $links
   * @return array|NULL[][]|array
   */
  protected  function processMenuLink($links) {
    if(empty($links)) return [];
    $data = [];
    foreach ($links as $key => $item) {
      if($item->link instanceof InaccessibleMenuLink) continue;
      $data[$key] = [
        'url' => $item->link->getUrlObject()->toString(),
        'title' => $item->link->getTitle(),
        'options' => $item->link->getOptions(),
        'active' => $item->inActiveTrail,
      ];
      if($item->hasChildren) {
        $data[$key]['subtree'] = $this->processMenuLink($item->subtree);
      }
    }
    return $data;
  }
}
