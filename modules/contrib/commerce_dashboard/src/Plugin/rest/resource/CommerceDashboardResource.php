<?php

namespace Drupal\commerce_dashboard\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the dashboard data Resource.
 *
 * @RestResource(
 *   id = "commerce_dashboard",
 *   label = @Translation("Commerce dashboard data"),
 *   uri_paths = {
 *     "canonical" = "/commerce_dashboard/data"
 *   }
 * )
 */
class CommerceDashboardResource extends ResourceBase {

  /**
   * The current active database's master connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * An entity type manager service instance.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->database = $container->get('database');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * Responds to entity GET requests.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The rest resource response.
   */
  public function get() {
    $sales = $this->getSalesData();
    $top_products = $this->getTopProducts();
    $carts = $this->getCarts();
    $response = [
      'sales' => $sales,
      'topProducts' => $top_products,
      'carts' => $carts,
    ];
    return new ResourceResponse($response);
  }

  /**
   * {@inheritdoc}
   */
  public function getSalesData() {
    $order_storage = $this->entityTypeManager->getStorage('commerce_order');
    $query = $order_storage->getQuery();
    $query->condition('state', 'completed');
    $query->condition('placed', strtotime('now -14 days'), '>=');
    $query->accessCheck(FALSE);
    $order_ids = $query->execute();
    $order_storage = $order_storage->loadMultiple($order_ids);
    $totals = [
      'today' => 0,
      'yesterday' => 0,
      'prevYesterday' => 0,
      'week' => 0,
      'prevWeek' => 0,
      'lineChart' => [],
    ];
    $day = strtotime('now -14 days');
    do {
      $totals['lineChart'][date('Y-m-d', $day)] = 0;
      $day++;
    } while ($day < time());

    foreach ($order_storage as $order) {
      if (date('Y-m-d', $order->getPlacedTime()) == date('Y-m-d')) {
        $totals['today'] += $order->getTotalPrice()->getNumber();
      }
      if (date('Y-m-d', $order->getPlacedTime()) == date('Y-m-d', strtotime('yesterday'))) {
        $totals['yesterday'] += $order->getTotalPrice()->getNumber();
      }
      if (date('Y-m-d', $order->getPlacedTime()) == date('Y-m-d', strtotime('now -2 days'))) {
        $totals['prevYesterday'] += $order->getTotalPrice()->getNumber();
      }
      if ($order->getPlacedTime() > strtotime('now -7 days')) {
        $totals['week'] += $order->getTotalPrice()->getNumber();
      }
      else {
        $totals['prevWeek'] += $order->getTotalPrice()->getNumber();
      }
      $totals['lineChart'][date('Y-m-d', $order->getPlacedTime())] += $order->getTotalPrice()->getNumber();
    }

    return [
      'today' => [
        'amount' => $totals['today'],
        'changeIndicator' => ($totals['today'] > $totals['yesterday']) ? 'increased' : 'decreased',
        'changePercentage' => '7',
      ],
      'yesterday' => [
        'amount' => $totals['yesterday'],
        'changeIndicator' => ($totals['yesterday'] > $totals['prevYesterday']) ? 'increased' : 'decreased',
        'changePercentage' => '7',
      ],
      'week' => [
        'amount' => $totals['week'],
        'changeIndicator' => ($totals['week'] > $totals['prevWeek']) ? 'increased' : 'decreased',
        'changePercentage' => '7',
      ],
      'lineChart' => $totals['lineChart'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getTopProducts() {
    $query = $this->database->select('commerce_order', 'commerce_order');
    $query->addJoin('LEFT', 'commerce_order_item', 'order_item', 'order_item.order_id = commerce_order.order_id');
    $query->fields('order_item', ['title', 'purchased_entity', 'total_price__number', 'quantity']);
    $query->condition('commerce_order.placed', strtotime('now -14 days'), '>=');
    $results = $query->execute();
    $top_products = [];
    foreach ($results as $record) {
      $top_products[$record->purchased_entity]['title'] = $record->title;
      $top_products[$record->purchased_entity]['total'] += $record->total_price__number;
      $top_products[$record->purchased_entity]['purchases'] += $record->quantity;
    }
    return $top_products;
  }

  /**
   * {@inheritdoc}
   */
  public function getCarts() {
    $order_storage = $this->entityTypeManager->getStorage('commerce_order');
    $query = $order_storage->getQuery();
    $query->condition('state', 'draft');
    $query->accessCheck(FALSE);
    $query->sort('changed', 'DESC');
    $order_ids = $query->execute();
    $order_storage = $order_storage->loadMultiple($order_ids);
    $carts = [];
    foreach ($order_storage as $order) {
      $total = $order->getTotalPrice() !== NULL ? $order->getTotalPrice()->getNumber() : 0;
      $carts[] = [
        'cartId' => $order->id(),
        'totalAmount' => number_format($total, 2),
        'created' => $order->getCreatedTime(),
        'changed' => $order->getChangedTime(),
      ];
    }
    return $carts;
  }

}
