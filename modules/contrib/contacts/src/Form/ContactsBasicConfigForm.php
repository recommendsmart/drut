<?php

namespace Drupal\contacts\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Basic config for contacts.
 *
 * @package Drupal\contacts\Form
 */
class ContactsBasicConfigForm extends ConfigFormBase {

  /**
   * The route builder service.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routeBuilder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /* @var static $form_instance */
    $form_instance = parent::create($container);
    $form_instance->routeBuilder = $container->get('router.builder');
    return $form_instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['contacts.configuration'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'contacts_basic_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('contacts.configuration');

    $form['redirect_user_page'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Redirect user page'),
      '#default_value' => $config->get('redirect_user_page'),
      '#description' => $this->t('When checked, will redirect requests from /user/{user} to the user dashboard'),
    ];

    $form['warning'] = [
      'heading' => ['#markup' => $this->t('<h3>Important Note</h3>')],
      'text' => ['#markup' => $this->t('Saving changes on this page will cause the routing table to be rebuilt.')],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('contacts.configuration')
      ->set('redirect_user_page', $form_state->getValue('redirect_user_page'))
      ->save();

    $this->routeBuilder->rebuild();
  }

}
