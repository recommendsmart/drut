<?php

namespace Drupal\smart_trim_readmore\Truncate;

use Drupal\smart_trim\Truncate\TruncateHTML;

/**
 * Class ExtraTruncateHTML.
 */
class ExtraTruncateHTML extends TruncateHTML {

  /**
   * Indicates if the truncate has been applied.
   *
   * @var bool
   */
  protected $truncated = FALSE;

  /**
   * {@inheritdoc}
   */
  public function truncateChars($html, $limit, $ellipsis = '...') {
    $result = parent::truncateChars($html, $limit, $ellipsis);
    $this->truncated = isset($this->limit);
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function truncateWords($html, $limit, $ellipsis = '...') {
    $result = parent::truncateWords($html, $limit, $ellipsis);
    $this->truncated = isset($this->limit);
    return $result;
  }

  /**
   * Checks if the truncate has been applied.
   *
   * @return bool
   */
  public function isTruncated() {
    return $this->truncated;
  }

}
