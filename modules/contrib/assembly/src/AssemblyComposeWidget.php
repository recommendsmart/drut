<?php

namespace Drupal\assembly;

use Drupal\Component\Utility\SortArray;
use Drupal\compose\EntityComposeWidget;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Component\Render\FormattableMarkup;
use \Drupal\Core\Entity\Query\QueryInterface;

/**
 * Add Assembly icons to Compose.
 */
class AssemblyComposeWidget extends EntityComposeWidget {

  /**
   * {@inheritdoc}
   */
  public function addNew($bundle_id) {

    $row = parent::addNew($bundle_id);

    $bundle = $this->entityTypeManager->getStorage($this->entityType->getBundleEntityType())->load($bundle_id);
    if (isset($bundle->icon) && !empty($bundle->icon)) {
      $row['image'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => [
          'class' => ['compose-preview-icon', 'add-new-left'],
        ],
        '#weight' => 0,
        'img' => [
          '#type' => 'html_tag',
          '#tag' => 'img',
          '#attributes' => [
            'src' => $bundle->icon,
            'alt' => $bundle->label(),
            'title' => $bundle->label(),
          ],
        ],
      ];
      $row['#attributes']['class'][] = 'has-icon';
    }

    uasort($row, [SortArray::class, 'sortByWeightProperty']);

    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function getAddExistingFilters(array $bundles = []) {
    return [
      'name' => [
        'field' => 'name',
        'label' => 'Name',
        'operator' => 'CONTAINS',
      ],
    ] +  parent::getAddExistingFilters($bundles);

  }

  /**
   * {@inheritdoc}
   */
  public function getAddExistingSort(QueryInterface $query) {
    $query->sort('name', 'ASC');

    return $query;
  }

}
