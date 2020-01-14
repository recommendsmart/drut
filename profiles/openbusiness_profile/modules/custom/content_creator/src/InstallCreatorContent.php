<?php

namespace Drupal\content_creator;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\File\FileSystemInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a helper class for importing default content.
 *
 * @internal
 *   This code is only for use by the Openbusiness profile: Content Creator.
 */
class InstallCreatorContent implements ContainerInjectionInterface {

  /**
   * The path alias manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * State.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a new InstallHelper object.
   *
   * @param \Drupal\Core\Path\AliasManagerInterface $aliasManager
   *   The path alias manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   Module handler.
   * @param \Drupal\Core\State\StateInterface $state
   *   State service.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system.
   */
  public function __construct(AliasManagerInterface $aliasManager, EntityTypeManagerInterface $entityTypeManager, ModuleHandlerInterface $moduleHandler, StateInterface $state, FileSystemInterface $fileSystem) {
    $this->aliasManager = $aliasManager;
    $this->moduleHandler = $moduleHandler;
    $this->entityTypeManager = $entityTypeManager;
    $this->state = $state;
    $this->fileSystem = $fileSystem;
  }

  /**
   * Instantiates a new instance of this class.
   *
   * This is a factory method that returns a new instance of this class. The
   * factory should pass any needed dependencies into the constructor of this
   * class, but not the container itself. Every call to this method must return
   * a new instance of this class; that is, it may not implement a singleton.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container this instance should use.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('path.alias_manager'),
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('state'),
      $container->get('file_system')
    );
  }

  /**
   * Getting path for module.
   */
  protected function getModulePath($entity_type, $bundle_machine_name) {
    $module_path = $this->moduleHandler->getModule('content_creator')
      ->getPath() . '/content/' . $entity_type . '/' . $bundle_machine_name . '.csv';
    return $module_path;
  }

  /**
   * Getting path for module.
   */
  protected function getImagesPath($image_name) {
    $ext = pathinfo($image_name, PATHINFO_EXTENSION);
    if ($ext == 'png') {
      $image_path = $this->moduleHandler->getModule('content_creator')
        ->getPath() . '/content/images/' . $image_name;
    }
    else {
      $image_path = $this->moduleHandler->getModule('content_creator')
        ->getPath() . '/content/files/' . $image_name;
    }
    return $image_path;
  }

  /**
   * Imports contents.
   */
  public function createContent() {
    $this->importContentFromFile('menu', 'main');
    $this->importContentFromFile('menu', 'terms-privacy');
    $this->importContentFromFile('menu', 'social-links');
    $this->importContentFromFile('node', 'testimonials');
    $this->importContentFromFile('block', 'hero_image');
    $this->importContentFromFile('block', 'basic');
    $this->importContentFromFile('term', 'tags');
    $this->importContentFromFile('node', 'article');
    $this->importContentFromFile('node', 'portofolio');
    $this->importContentFromFile('node', 'page');
  }

  /**
   * Getting specific content from specific csv.
   */
  protected function importContentFromFile($entity_type, $bundle_machine_name) {
    $module_path = $this->getModulePath($entity_type, $bundle_machine_name);
    $data = array_map('str_getcsv', file($module_path));
    switch ($entity_type) {
      case 'node':
        $this->importNodes($data, $bundle_machine_name);
        break;

      case 'menu':
        $this->importMenus($data, $bundle_machine_name);
        break;

      case 'user':
        $this->importUsers($data, $bundle_machine_name);
        break;

      case 'block':
        $this->importBlocks($data, $bundle_machine_name);
        break;

      case 'term':
        $this->importTerms($data, $bundle_machine_name);
        break;
    }
  }

  /**
   * Creating media with entityTypeManager.
   */
  protected function createMedia($imagePath) {
    $filename = basename($imagePath);
    $uri = $this->fileSystem->copy($imagePath, 'public://' . $filename, FileSystemInterface::EXISTS_REPLACE);
    $file = $this->entityTypeManager->getStorage('file')->create([
      'uri' => $uri,
      'status' => 1,
    ]);
    $file->save();
    $this->generateUuids([$file->uuid() => 'file']);
    return $file->id();
  }

  /**
   * Stores record of content entities created by this import.
   *
   * @param array $uuids
   *   Array of UUIDs where the key is the UUID and the value is the entity
   *   type.
   */
  protected function generateUuids(array $uuids) {
    $uuids = $this->state->get('content_creator_uuids', []) + $uuids;
    $this->state->set('content_creator_uuids', $uuids);
  }

  /**
   * Function who imports content from html files.
   */
  protected function importHtmlFromFile($file) {
    $file = file_get_contents($this->moduleHandler->getModule('content_creator')
      ->getPath() . '/content/html/' . $file);
    return $file;
  }

  /**
   * Function who imports nodes.
   */
  protected function importBlocks($data, $type) {
    foreach (array_slice($data, 1) as $line) {
      $generator = [
        'type' => $type,
        'info' => $line[1],
      ];
      if ($type === 'hero_image') {
        $landscape = $this->getImagesPath($line[2]);
        $landscape = $this->createMedia($landscape);
        $portrait = $this->getImagesPath($line[3]);
        $portrait = $this->createMedia($portrait);
        $add_image = [
          'field_landscape' => [
            'target_id' => $landscape,
            'alt' => $line[4],
          ],
          'field_portrait' => [
            'target_id' => $portrait,
            'alt' => $line[4],
          ],
          'field_hero_title' => [
            'value' => $line[5],
            'format' => 'full_html',
          ],
          'field_hero_text' => [
            'value' => $line[6],
            'format' => 'full_html',
          ],
          'field_hero_link' => [
            'uri' => 'internal:/' . $line[7],
            'title' => $line[8],
          ],
        ];
        $generator = $generator + $add_image;
      }
      if ($type === 'basic') {
        $add_body = [
          "body" => [
            'value' => $line[2],
            'format' => 'full_html',
          ],
        ];
        $generator = $generator + $add_body;
      }
      $this->entityTypeManager->getStorage('block_content')
        ->create($generator)->save();
      $this->placeBlock($type, $line[1], $line[0]);
    }
  }

  /**
   * Function for placing block.
   */
  protected function placeBlock($type, $name, $region) {
    $block = $this->entityTypeManager->getStorage('block_content')
      ->loadByProperties(['type' => $type]);
    $id = array_keys($block);

    $generate = [
      'id' => strtolower(str_replace(" ", "_", $name)),
      'theme' => 'openbusiness_theme',
      'weight' => -7,
      'status' => TRUE,
      'region' => $region,
      'plugin' => 'block_content:' . $block[$id[0]]->uuid(),
      'settings' => [],
    ];

    if ($type === 'hero_image') {
      $visible = [
        'visibility' => [
          'request_path' => [
            'id' => 'request_path',
            'pages' => '<front>',
          ],
        ],
      ];
      $generate = $generate + $visible;
    }
    $this->entityTypeManager->getStorage('block')->create($generate)->save();
  }

  /**
   * Function who imports terms.
   */
  protected function importTerms($data, $type) {
    foreach (array_slice($data, 1) as $line) {
      $generator = [
        'name' => $line[0],
        'vid' => $type,
        'path' => [
          'alias' => '/blog/' . strtolower($line[0]),
        ],
      ];
      $this->entityTypeManager->getStorage('taxonomy_term')
        ->create($generator)->save();
      }
  }

  /**
   * Function who setting image.
   */
  protected function setImage($data) {
    $image = $this->getImagesPath($data);
    $image_media = $this->createMedia($image);
    return $image_media;
  }

  /**
   * Function who imports nodes.
   */
  protected function importNodes($data, $type) {
    foreach (array_slice($data, 1) as $line) {
      $generator = [
        'type' => $type,
        'title' => $line[0],
        'uid' => 1,
      ];
      if ($type === 'page') {
        if ($line[2] == 1) {
          $settings_page = [
            'promote' => $line[2],
          ];
        }
        else {
          $path = $arr = explode(' ', trim($line[0]));
          $settings_page = [
            'path' => [
              'alias' => '/' . strtolower($path[0]),
            ],
          ];
        }
        $generator = $generator + $settings_page;
        if (!empty($line[3])) {
          $generator_para = $this->addParagraphToNode($line[3]);
          $generator = $generator + $generator_para;
        }
      }
      if ($type === 'article') {
        $term = $this->entityTypeManager->getStorage('taxonomy_term')
          ->loadByProperties(['name' => $line[3]]);
        $tags = [
          'field_tags' => [
            'target_id' => key($term),
          ],
        ];
        $generator = $generator + $tags;
        if (!empty($line[4])) {
          $generator_para = $this->addParagraphToNode($line[4]);
          $generator = $generator + $generator_para;
        }
      }
      if ($type === 'testimonials') {
        $field_role = [
          'field_role' => $line[3],
          'body' => [
            'value' => $line[1],
            'format' => 'full_html',
          ],
        ];
        $generator = $generator + $field_role;
      }
      if ($type === "testimonials" || $type === 'portofolio' || $type === 'article') {
        $field_image = [
          'field_image' => [
            'target_id' => $this->setImage($line[2]),
            'alt' => $line[0],
          ],
        ];
        $generator = $generator + $field_image;
        if ($type === 'portofolio' || $type === 'article') {
          $path = str_replace(' ', '-', $line[0]);
          $path_alias = [
            'path' => [
              'alias' => '/' . $type . '/' . $path,
            ],
          ];
          $generator = $generator + $path_alias;
          if ($type === 'portofolio' && !empty($line[3])) {
            $generator_para = $this->addParagraphToNode($line[3]);
            $generator = $generator + $generator_para;
          }
        }
      }
      if ($type !== 'testimonials') {
        $get_body = $this->importHtmlFromFile($line[1]);
        $field_body = [
          'body' => [
            'value' => $get_body,
            'format' => 'full_html',
          ],
        ];
        $generator = $generator + $field_body;
      }
      $this->entityTypeManager->getStorage('node')->create($generator)->save();
    }
  }

  /**
   * Function which add paragraph fields to node.
   */
  protected function addParagraphToNode($file) {
    $pieces = explode(",", $file);
    foreach ($pieces as $paragraph_line) {
      $string = preg_replace('/[0-9]+/', '', $paragraph_line);
      $paragraph = $this->createParagraph($paragraph_line, $string);
      $array[] = [
        'target_id' => $paragraph->id(),
        'target_revision_id' => $paragraph->getRevisionId(),
      ];
    }
    $generator_para = [
      'field_element' => $array,
    ];
    return $generator_para;
  }

  /**
   * Function which create paragraphs.
   */
  protected function createParagraph($origin, $edited) {
    $module_path = $this->getModulePath('paragraphs', $origin);
    $data = array_map('str_getcsv', file($module_path));
    switch ($edited) {
      case 'block_quote':
        foreach (array_slice($data, 1) as $line) {
          $paragraph = $this->entityTypeManager->getStorage('paragraph')
            ->create([
              'type' => 'block_quote',
              'field_body' => $line[0],
            ]);
          $paragraph->save();
        }
        break;

      case 'image_list':
        $paragraph = [
          'type' => 'image_list',
        ];
        $pieces = explode(",", $data[1][0]);
        $x = 1;
        foreach ($pieces as $line) {
          $array = [
            'field_image' . $x => [
              'target_id' => $this->setImage($line),
              'alt' => 'OpenBusiness Images',
            ],
          ];
          $x++;
          $paragraph = $paragraph + $array;
        }
        $paragraph = $this->entityTypeManager->getStorage('paragraph')
          ->create($paragraph);
        $paragraph->save();

        break;

      case 'paragraph_with_body':
        $paragraph = $this->entityTypeManager->getStorage('paragraph')->create([
          'type' => 'paragraph_with_body',
          'field_title' => [
            'value' => $data[1][0],
            'format' => 'full_html',
          ],
          'field_body' => [
            'value' => $data[1][1],
            'format' => 'full_html',
          ],
        ]);
        $paragraph->save();

        break;

      case 'paragraph_with_image':
        $paragraph = [
          'type' => 'paragraph_with_image',
        ];
        foreach (array_slice($data, 1) as $line) {
          $array = [
            'field_image1' => [
              'target_id' => $this->setImage($line[0]),
              'alt' => 'OpenBusiness Images',
            ],
            'field_title' => [
              'value' => $line[1],
              'format' => 'full_html',
            ],
            'field_body' => [
              'value' => $line[2],
              'format' => 'full_html',
            ],
            'field_image_position' => [
              'value' => $line[3],
              'format' => 'full_html',
            ],
          ];
        }
        $paragraph = $paragraph + $array;
        $paragraph = $this->entityTypeManager->getStorage('paragraph')
          ->create($paragraph);
        $paragraph->save();

        break;

      case 'carousel':
        $paragraph = [
          'type' => 'carousel',
        ];
        $pieces = explode(",", $data[1][0]);
        foreach ($pieces as $line) {
          $array[] = [
            'target_id' => $this->setImage($line),
            'alt' => 'OpenBusiness Images',
          ];
          $paragraph['field_image'] = $array;
        }
        $paragraph = $this->entityTypeManager->getStorage('paragraph')
          ->create($paragraph);
        $paragraph->save();

        break;

      case 'attachment':
        $paragraph = [
          'type' => 'attachment',
        ];
        $pieces = explode(",", $data[1][0]);
        foreach ($pieces as $line) {
          $array[] = [
            'target_id' => $this->setImage($line),
            'alt' => 'OpenBusiness Images',
          ];
          $paragraph['field_fiels'] = $array;
        }
        $paragraph = $this->entityTypeManager->getStorage('paragraph')
          ->create($paragraph);
        $paragraph->save();

        break;
    }
    return $paragraph;
  }

  /**
   * Function which imports menu links for the main menu.
   */
  protected function importMenus($data, $type) {
    $i = 0;
    // Links for the main menu.
    foreach (array_slice($data, 1) as $line) {
        if ($line[2] == 0) {
          $route = [
            'link' => [
              'uri' => $line[1],
            ],
          ];
        }
        else {
          $route = [
            'link' => [
              'uri' => 'internal:/' . $line[1],
            ],
          ];
        }
        $general = [
          'enabled' => 1,
          'title' => $line[0],
          'menu_name' => $type,
          'weight' => $i++,
        ];
        $general = $general + $route;
        $this->entityTypeManager->getStorage('menu_link_content')
          ->create($general)
          ->save();
      }
    }
}
