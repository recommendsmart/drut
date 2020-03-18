<?php

namespace Drupal\cbo_organization\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for deleting a organization.
 */
class OrganizationDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {


    return parent::buildForm($form, $form_state);
  }

}
