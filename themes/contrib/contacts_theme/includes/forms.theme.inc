<?php

/**
 * @file
 * Form related theme functions and hooks for Contacts Theme.
 */

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Render\Element;
use Drupal\Core\Template\Attribute;

/**
 * Pre render callback to propagate form column settings.
 *
 * @param array $element
 *   The element array.
 * @param array|null $column_settings
 *   @internal The column settings to propagate.
 *
 * @return array
 *   The adjusted element.
 */
function contacts_theme_form_propagate_columns(array $element, $column_settings = NULL) {
  // If not set, we either propagate our given value or there is nothing to do.
  if (!isset($element['#form_columns'])) {
    if (isset($column_settings)) {
      $element['#form_columns'] = $column_settings;
    }
    else {
      return $element;
    }
  }
  // Otherwise, process the form columns, merging in the defaults.
  elseif (isset($element['#form_columns'])) {
    // Convert non arrays to an empty array.
    if (!is_array($element['#form_columns'])) {
      $element['#form_columns'] = array();
    }

    // Add our default settings.
    $element['#form_columns'] += array(
      'label' => ['col-sm-5', 'col-md-4', 'col-lg-4', 'col-xl-3'],
      'input' => ['col-sm-7', 'col-md-7', 'col-lg-8', 'col-xl-6'],
      'errors' => ['col-md-8', 'col-lg-8', 'col-xl-4', 'offset-md-4', 'offset-lg-3', 'offset-xl-0'],
      'description' => ['col-md-8', 'col-lg-8', 'col-xl-9', 'offset-md-4', 'offset-lg-4', 'offset-xl-3'],
    );

    // Default the offset classes to line up with the input.
    if (!isset($element['#form_columns']['_offset'])) {
      $element['#form_columns']['_offset'] = array_map(function($class){
        return str_replace('col-', 'offset-', $class);
      }, $element['#form_columns']['label']);
    }

    // If description or error are FALSE, we use the input/offset classes.
    $classes = array_merge($element['#form_columns']['input'], $element['#form_columns']['_offset'], ['flex-last']);
    if (!$element['#form_columns']['description']) {
      $element['#form_columns']['description'] = $classes;
    }
    if (!$element['#form_columns']['errors']) {
      $element['#form_columns']['errors'] = $classes;
    }
  }

  // Propagate to our children.
  foreach (Element::children($element) as $child) {
    $element[$child] = contacts_theme_form_propagate_columns($element[$child], $element['#form_columns']);
  }

  return $element;
}

/**
 * Implements hook_preprocess_HOOK().
 */
function contacts_theme_preprocess_form_element(&$variables) {
  $variables['element'] += [
    '#form_group' => TRUE,
  ];
  $variables += [
    'use_wrapper' => empty($variables['element']['#no_wrapper']),
    'form_group' => !empty($variables['element']['#form_group']),
    'type' => NULL,
    'wrap_in_label' => isset($variables['type']) && in_array($variables['type'], ['radio', 'checkbox']) && $variables['title_display'] == 'after',
    // Support for inline form errors.
    'error_attributes' => new Attribute(),
  ];

  if ($variables['use_wrapper']) {
    if ($variables['form_group']) {
      $variables['attributes']['class'][] = 'form-group';
    }

    // Add error classes.
    if (!empty($variables['element']['#errors'])) {
      $variables['attributes']['class'][] = 'has-danger';
    }

    if (!empty($variables['element']['#form_columns']) && $variables['form_group']) {
      $variables['attributes']['class'][] = 'row';

      if ($variables['title_display'] == 'before') {
        $variables['label']['#attributes'] += ['class' => []];
        $variables['label']['#attributes']['class'] = array_merge($variables['label']['#attributes']['class'], $variables['element']['#form_columns']['label']);

        // If we are not composite fields (radios/checkboxes) use the inline label.
        if (!in_array($variables['type'], ['radios', 'checkboxes'])) {
          $variables['label']['#attributes']['class'][] = 'col-form-label';
        }
      }

      // Add in additional classes if the title isn't displayed before.
      $classes = $variables['element']['#form_columns']['input'];
      // If there is no label, assume child items provide labels.
      if ($variables['title_display'] == 'none') {
        $classes = ['col-12'];
      }
      elseif ($variables['title_display'] != 'before') {
        $classes = array_merge($classes, $variables['element']['#form_columns']['_offset']);
      }

      // If wrapping, we need to add a title prefix/suffix.
      if ($variables['wrap_in_label']) {
        $variables['label']['#prefix'] = '<div class="' . implode(' ', $classes) . '">';
        $variables['label']['#suffix'] = '</div>';
      }
      // Otherwise wrap the children in a div.
      else {
        $variables['children'] = new FormattableMarkup('<div class="@classes">@children</div>', [
          '@classes' => implode(' ', $classes),
          '@children' => $variables['children'],
        ]);
      }

      // Add classes to the description and errors.
      if ($variables['description']) {
        $variables['description']['attributes']->addClass($variables['element']['#form_columns']['description']);
      }
      $variables['error_attributes']->addClass($variables['element']['#form_columns']['errors']);
    }
  }

  if ($variables['description']) {
    $variables['description']['attributes']->addClass('form-text text-muted');
  }

  // Make radios and checkboxes wrap their label for 'after' display.
  if ($variables['wrap_in_label']) {
    $variables['label']['#attributes']['class'][] = 'form-check-label';
    $variables['label']['#title'] = new FormattableMarkup('@children @title', [
      '@title' => $variables['label']['#title'],
      '@children' => $variables['children'],
    ]);
    $variables['children'] = '';
  }

  // Process any element that has declared it's children are addons.
  if (!empty($variables['element']['#has_addons'])) {
    $add_ons = Element::children($variables['element']);
    if ($add_ons) {
      $children = [
        '_main' => [
          '#markup' => $variables['children'],
          '#weight' => 0,
        ],
      ];
      foreach ($add_ons as $add_on) {
        $children[$add_on] = $variables['element'][$add_on];

        // Remove the form_element wrapper.
        if (!empty($children[$add_on]['#theme_wrappers'])) {
          $children[$add_on]['#theme_wrappers'] = array_diff($children[$add_on]['#theme_wrappers'], ['form_element']);
        }

        $add_on_type = $children[$add_on]['#type'] == 'submit' ? 'btn' : 'addon';
        $children[$add_on]['#prefix'] = '<span class="input-group-' . $add_on_type . '">';
        $children[$add_on]['#suffix'] = '</span>';
      }

      // Wrap in the input group container.
      $children['#prefix'] = '<div class="input-group">';
      $children['#suffix'] = '</div>';

      // Render, replacing the existing.
      $variables['children'] = \Drupal::service('renderer')->render($children);
    }
  }
}

/**
 * Implements hook_preprocess_HOOK().
 */
function contacts_theme_preprocess_textarea(&$variables) {
  $variables['attributes']->addClass('form-control');
}

/**
 * Implements hook_preprocess_HOOK().
 */
function contacts_theme_preprocess_input(&$variables) {
  switch ($variables['element']['#type']) {
    case 'token':
    case 'hidden':
      // Do nothing.
      break;

    case 'submit':
      // If we already have the btn class, assume all other classes are as desired.
      if (!in_array('btn', $variables['attributes']['class'])) {
        $variables['attributes']['class'][] = 'btn';
        $variables['attributes']['class'] = array_diff($variables['attributes']['class'], ['button']);

        // If we have the 'button--primary' class, add our primary.
        if (in_array('button--primary', $variables['attributes']['class'])) {
          $variables['attributes']['class'][] = 'btn-primary';
          $variables['attributes']['class'] = array_diff($variables['attributes']['class'], ['button--primary']);
        }
        // Otherwise assume it's secondary.
        else {
          $variables['attributes']['class'][] = 'btn-secondary';
        }
      }
      break;

    case 'checkbox':
    case 'radio':
      $variables['attributes']['class'][] = $variables['element']['#title_display'] == 'after' ? 'form-check-input' : 'form-check';
      break;

    case 'file':
      $variables['attributes']['class'][] = 'form-control-file';
      break;

    default:
      $variables['attributes']['class'][] = 'form-control';

      // Swap .form-text for .form-textfield as it conflicts in bootstrap.
      if (($pos = array_search('form-text', $variables['attributes']['class'])) !== FALSE) {
        $variables['attributes']['class'][$pos] = 'form-textfield';
      }
      break;
  }
}

/**
 * Pre render callback for the actions element.
 */
function contacts_theme_pre_render_actions($element) {
  // Align actions with the main column.
  if (!empty($element['#form_columns'])) {
    $element['#prefix'] = '<div class="row">';
    $element['#suffix'] = '</div>';

    $classes = $element['#form_columns']['input'];
    foreach ($element['#form_columns']['label'] as $class) {
      $classes[] = str_replace('col-', 'offset-', $class);
    }

    $element['#attributes'] += ['class' => []];
    $element['#attributes']['class'] = array_merge($element['#attributes']['class'], $classes);
  }

  return $element;
}

/**
 * Implements hook_preprocess_HOOK().
 */
function contacts_theme_preprocess_select(&$variables) {
  $variables['attributes']['class'][] = 'form-control';
  $variables['attributes']['class'][] = 'custom-select';
}

/**
 * Implements hook_preprocess_HOOK().
 */
function contacts_theme_preprocess_datetime_form(&$variables) {
  $variables['attributes']['class'][] = 'form-inline';
  $variables['content'] = contacts_theme_make_inline($variables['content']);
}

/**
 * Pre-render to remove wrappers on child elements.
 */
function contacts_theme_make_inline($element) {
  foreach (Element::children($element) as $child) {
    if (in_array('form_element', $element[$child]['#theme_wrappers'])) {
      $element[$child]['#no_wrapper'] = TRUE;
      $element[$child]['#attributes']['class'][] = 'mr-sm-2';
      $element[$child]['#attributes']['class'][] = 'mb-2';
      $element[$child]['#attributes']['class'][] = 'mb-sm-0';
    }
  }
  return $element;
}

/**
 * Pre-render to make composite children not form groups..
 */
function contacts_theme_composite_pre_render($element) {
  foreach (Element::children($element) as $child) {
    $element[$child]['#form_group'] = FALSE;
  }
  return $element;
}

/**
 * Pre-render callback for containers to prevent rendering empty elements.
 */
function contacts_theme_pre_render_ignore_empty($element) {
  // If already printed or access is FALSE, we don't need to do any more.
  if (!empty($element['#printed']) || !contacts_theme_check_access($element)) {
    return $element;
  }

  // Look for any accessible children.
  $has_children = FALSE;
  foreach (Element::children($element) as $key) {
    if (contacts_theme_check_access($element[$key])) {
      $has_children = TRUE;
      break;
    }
  }

  // Prevent rendering if there are no accessible children.
  if (!$has_children) {
    $element['#printed'] = TRUE;
  }

  return $element;
}

/**
 * Implements hook_preprocess() for checkboxes.
 */
function contacts_theme_preprocess_checkboxes(array &$variables) {
  if (isset($variables['element']['#attributes'])) {
    $variables['attributes'] = array_merge($variables['element']['#attributes']);
  }
}

/**
 * Implements hook_preprocess() for radios.
 */
function contacts_theme_preprocess_radios(array &$variables) {
  if (isset($variables['element']['#attributes'])) {
    $variables['attributes'] = array_merge($variables['element']['#attributes']);
  }
}
