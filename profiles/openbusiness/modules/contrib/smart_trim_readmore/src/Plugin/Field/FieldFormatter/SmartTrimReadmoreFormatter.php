<?php

namespace Drupal\smart_trim_readmore\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\smart_trim\Plugin\Field\FieldFormatter\SmartTrimFormatter;
use Drupal\smart_trim_readmore\Truncate\ExtraTruncateHTML;

/**
 * Plugin implementation of the 'smart_trim_readmore' formatter.
 *
 * @FieldFormatter(
 *   id = "smart_trim_readmore",
 *   label = @Translation("Smart trimmed (readmore)"),
 *   field_types = {
 *     "text",
 *     "text_long",
 *     "text_with_summary",
 *     "string",
 *     "string_long"
 *   }
 * )
 */
class SmartTrimReadmoreFormatter extends SmartTrimFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'less_link' => 0,
      'less_class' => 'less-link',
      'less_text' => 'Less',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['less_link'] = [
      '#title' => $this->t('Display less link?'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('less_link'),
      '#description' => $this->t('Displays a link to the entity (if one exists)'),
    ];

    $element['less_text'] = [
      '#title' => $this->t('Less link text'),
      '#type' => 'textfield',
      '#size' => 20,
      '#default_value' => $this->getSetting('less_text'),
      '#description' => $this->t('If displaying less link, enter the text for the link.'),
      '#states' => [
        'visible' => [
          ':input[name="fields[body][settings_edit_form][settings][less_link]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $element['less_class'] = [
      '#title' => $this->t('Less link class'),
      '#type' => 'textfield',
      '#size' => 20,
      '#default_value' => $this->getSetting('less_class'),
      '#description' => $this->t('If displaying less link, add a custom class for formatting.'),
      '#states' => [
        'visible' => [
          ':input[name="fields[body][settings_edit_form][settings][less_link]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    if ($this->getSetting('less_link')) {
      $summary[] = $this->t("With less link");
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode = NULL) {
    $element = [];
    $setting_trim_options = $this->getSetting('trim_options');
    $settings_summary_handler = $this->getSetting('summary_handler');
    $entity = $items->getEntity();

    foreach ($items as $delta => $item) {
      $output = $item->value;
      $truncate = new ExtraTruncateHTML();
      if ($settings_summary_handler != 'ignore' && !empty($item->summary)) {
        $summary = $item->summary;
      }
      else {
        $summary = $output;
      }

      // Process additional options (currently only HTML on/off).
      if (!empty($setting_trim_options)) {
        // Allow a zero length trim.
        if (!empty($setting_trim_options['trim_zero']) && $this->getSetting('trim_length') == 0) {
          // If the summary is empty, trim to zero length.
          if (empty($item->summary)) {
            $summary = '';
          }
          elseif ($settings_summary_handler != 'full') {
            $summary = '';
          }
        }

        if (!empty($setting_trim_options['text'])) {
          // Strip caption.
          $summary = preg_replace('/<figcaption[^>]*>.*?<\/figcaption>/i', ' ', $summary);

          // Strip tags.
          $summary = strip_tags(str_replace('<', ' <', $summary));

          // Strip out line breaks.
          $summary = preg_replace('/\n|\r|\t/m', ' ', $summary);

          // Strip out non-breaking spaces.
          $summary = str_replace('&nbsp;', ' ', $summary);
          $summary = str_replace("\xc2\xa0", ' ', $summary);

          // Strip out extra spaces.
          $summary = trim(preg_replace('/\s\s+/', ' ', $summary));
        }
      }

      // Make the trim, provided we're not showing a full summary.
      if ($this->getSetting('summary_handler') != 'full' || empty($item->summary)) {
        $length = $this->getSetting('trim_length');
        $ellipse = $this->getSetting('trim_suffix');
        if ($this->getSetting('trim_type') == 'words') {
          $summary = $truncate->truncateWords($summary, $length, $ellipse);
        }
        else {
          $summary = $truncate->truncateChars($summary, $length, $ellipse);
        }
      }

      $summary_item = [
        '#type' => 'processed_text',
        '#text' => $summary,
        '#format' => $item->format,
      ];

      if ($truncate->isTruncated() && $entity->id() && $entity->hasLinkTemplate('canonical')) {
        $output_item = [
          '#type' => 'processed_text',
          '#text' => $output,
          '#format' => $item->format,
        ];

        // But wait! Don't add links if the field ends in <!--break-->.
        if (strpos(strrev($summary), strrev('<!--break-->')) !== 0) {
          if ($this->getSetting('more_link')) {
            $more = $this->getSetting('more_text');
            $class = $this->getSetting('more_class');

            $project_link = $entity->toLink($more)->toRenderable();
            $project_link['#attributes'] = [
              'class' => [
                $class,
              ],
            ];
            $project_link['#prefix'] = '<div class="' . $class . '">';
            $project_link['#suffix'] = '</div>';
            $summary_item['more_link'] = $project_link;
          }
          if ($this->getSetting('less_link')) {
            $less = $this->getSetting('less_text');
            $class = $this->getSetting('less_class');

            $project_link = $entity->toLink($less)->toRenderable();
            $project_link['#attributes'] = [
              'class' => [
                $class,
              ],
            ];
            $project_link['#prefix'] = '<div class="' . $class . '">';
            $project_link['#suffix'] = '</div>';
            $output_item['more_link'] = $project_link;
          }
        }

        $element[$delta] = [
          '#theme' => 'smart_trim_readmore',
          '#summary' => $summary_item,
          '#output' => $output_item,
        ];
      }
      else {
        $element[$delta] = $summary_item;
      }

      // Wrap content in container div.
      if ($this->getSetting('wrap_output')) {
        $element[$delta]['#prefix'] = '<div class="' . $this->getSetting('wrap_class') . '">';
        $element[$delta]['#suffix'] = '</div>';
      }
    }

    $element['#attached']['library'][] = 'smart_trim_readmore/smart_trim_readmore';

    return $element;
  }

}
