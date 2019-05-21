<?php

namespace Drupal\layout_builder_block_sanitizer\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\layout_builder_block_sanitizer\LayoutBuilderBlockSanitizerManager;

/**
 * Class SanitizerForm.
 */
class SanitizerForm extends FormBase {

  /**
   * The layout builder block sanitizer manager.
   *
   * @var Drupal\layout_builder_block_sanitizer\LayoutBuilderBlockSanitizerManager
   */
  protected $layoutBuilderBlockSanitizerManager;

  /**
   * Constructs a new SanitizerForm object.
   */
  public function __construct(
    LayoutBuilderBlockSanitizerManager $layout_builder_block_sanitizer_manager
  ) {
    $this->layoutBuilderBlockSanitizerManager = $layout_builder_block_sanitizer_manager;
  }

  /**
   * Create method.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('layout_builder_block_sanitizer.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sanitizer_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['node_to_sanitize'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Node to sanitize'),
      '#description' => $this->t('Enter a node ID to sanitize non-existent blocks from it.'),
      '#maxlength' => 64,
      '#size' => 64,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $nid_to_sanitize = $form_state->getValue('node_to_sanitize');
    $this->layoutBuilderBlockSanitizerManager->sanitizeNode($nid_to_sanitize);
  }

}
