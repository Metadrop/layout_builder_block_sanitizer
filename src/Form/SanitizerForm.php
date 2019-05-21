<?php

namespace Drupal\layout_builder_block_sanitizer\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Config\ConfigManager;
use Drupal\block_content\BlockContentUuidLookup;

/**
 * Class SanitizerForm.
 */
class SanitizerForm extends FormBase {

  /**
   * Drupal\Core\Entity\EntityManager definition.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;
  /**
   * Drupal\Core\Config\ConfigManager definition.
   *
   * @var \Drupal\Core\Config\ConfigManager
   */
  protected $configManager;
  /**
   * Drupal\block_content\BlockContentUuidLookup definition.
   *
   * @var \Drupal\block_content\BlockContentUuidLookup
   */
  protected $blockContentUuidLookup;

  /**
   * Constructs a new SanitizerForm object.
   */
  public function __construct(
    EntityManager $entity_manager,
    ConfigManager $config_manager,
    BlockContentUuidLookup $block_content_uuid_lookup
  ) {
    $this->entityManager = $entity_manager;
    $this->configManager = $config_manager;
    $this->blockContentUuidLookup = $block_content_uuid_lookup;
  }

  /**
   * Create method.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('config.manager'),
      $container->get('block_content.uuid_lookup')
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
    // Sanitize a node.

  }

}
