<?php

namespace Drupal\assembly\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\assembly\Entity\AssemblyTypeInterface;
use Drupal\Component\Render\FormattableMarkup;

/**
 * Class AssemblyTypeForm.
 *
 * @package Drupal\assembly\Form
 */
class AssemblyTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $assembly_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $assembly_type->label(),
      '#description' => $this->t("Label for the Assembly type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $assembly_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\assembly\Entity\AssemblyType::load',
      ],
      '#disabled' => !$assembly_type->isNew(),
    ];

    $form['description'] =[
      '#type' => 'textarea',
      '#title' => 'Description',
      '#default_value' => $assembly_type->description,
    ];

    if (isset($assembly_type->icon) && !empty($assembly_type->icon)) {
      $icon_file_title = 'Upload new assembly type icon';
      $form['image'] = [
        '#markup' => new FormattableMarkup('<label class="control-label">Current assembly type icon.</label><img src="@src">', ['@src' => $assembly_type->icon]),
      ];
    }
    else {
      $icon_file_title = 'Upload assembly type icon';
    }

    $form['icon_file'] =[
      '#title' => $this->t($icon_file_title),
      '#type' => 'file',
      '#upload_location' => AssemblyTypeInterface::ICON_UPLOAD_LOCATION,
      '#upload_validators' => [
        'file_validate_extensions' => ['png jpg svg'],
      ],
    ];

    $form['visual_styles'] =[
      '#type' => 'textarea',
      '#title' => 'Visual Styles',
      '#default_value' => $assembly_type->getVisualStyles(),
      '#description' => 'Provide a list of styles the user can choose. Styles are added as CSS classes when the content bar is rendered. Add one style per line, in the format: <br /><strong><em>CSS class</em>|<em>label</em>|<em>description of style</em></strong>',
    ];

    $form['new_revision'] = [
      '#type' => 'checkbox',
      '#title' => 'Create new revision',
      '#default_value' => $assembly_type->isNewRevision(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $files = $this->getRequest()->files->get('files', []);

    if (isset($files['icon_file'])) {
      $icon_file_path = $files['icon_file']->getRealPath();
      $icon_file_mime_type = $files['icon_file']->getClientMimeType();
      $form_state->setTemporaryValue('icon_file_uri', $icon_file_path);
      $form_state->setTemporaryValue('icon_file_mime_type', $icon_file_mime_type);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $assembly_type = $this->entity;

 		// Set the icon file to the assembly configuration.
    if (!empty($form_state->hasTemporaryValue('icon_file_uri'))) {
      $assembly_type->set(
        'icon',
        'data:' . $form_state->getTemporaryValue('icon_file_mime_type') . ';base64,' . base64_encode(file_get_contents($form_state->getTemporaryValue('icon_file_uri')))
      );
    }
    else {
      $assembly_type->set('icon', NULL);
    }

    $status = $assembly_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Assembly type.', [
          '%label' => $assembly_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Assembly type.', [
          '%label' => $assembly_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($assembly_type->toUrl('collection'));
  }

}
