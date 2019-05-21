<?php

namespace Drupal\layout_builder_block_sanitizer\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Config\ConfigManager;
use Drupal\block_content\BlockContentUuidLookup;
use Drupal\node\Entity\Node;
use Drupal\layout_builder\SectionStorage\SectionStorageManager;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\layout_builder\LayoutTempstoreRepositoryInterface;

/**
 * Class SanitizerForm.
 */
class SanitizerForm extends FormBase {

  /**
   * The layout tempstore repository.
   *
   * @var \Drupal\layout_builder\LayoutTempstoreRepositoryInterface
   */
  protected $layoutTempstoreRepository;

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
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

  protected $sectionStorageManager;

  /**
   * Constructs a new SanitizerForm object.
   */
  public function __construct(
    EntityTypeManager $entity_manager,
    ConfigManager $config_manager,
    BlockContentUuidLookup $block_content_uuid_lookup,
    SectionStorageManager $section_storage_manager,
    LayoutTempstoreRepositoryInterface $layout_tempstore_repository
  ) {
    $this->entityManager = $entity_manager;
    $this->configManager = $config_manager;
    $this->blockContentUuidLookup = $block_content_uuid_lookup;
    $this->sectionStorageManager = $section_storage_manager;
    $this->layoutTempstoreRepository = $layout_tempstore_repository;

  }

  /**
   * Create method.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('config.manager'),
      $container->get('block_content.uuid_lookup'),
      $container->get('plugin.manager.layout_builder.section_storage'),
      $container->get('layout_builder.tempstore_repository')
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
    try {
      $entity = Node::load($nid_to_sanitize);
      $types = [
        'overrides'
      ];
      foreach ($types as $type) {
        $contexts['entity'] = EntityContext::fromEntity($entity);
        $view_mode = 'full';
        $view_mode = LayoutBuilderEntityViewDisplay::collectRenderDisplay($entity, $view_mode)->getMode();
        $contexts['view_mode'] = new Context(new ContextDefinition('string'), $view_mode);
        $section_storage = $this->sectionStorageManager->load($type, $contexts);
        $section_storage = $this->layoutTempstoreRepository->get($section_storage);
        $id = $section_storage->getStorageId();
        $sections = $section_storage->getSections();
        // Check through each section's components to confirm blocks are valid.
        foreach ($sections as $key => &$section) {
          $components = $section->getComponents();
          foreach ($components as $section_component_uuid => $section_component) {
            $configuration = $section_component->get('configuration');
            $provider = $configuration['provider'];
            if ($provider == 'block_content') {
              $raw_id = $configuration['id'];
              $id = str_replace('block_content:', '', $raw_id);
              // Attempt to find a block w/ this UUID.
              $block = $this->blockContentUuidLookup->get($id);
              if ($block == NULL) {
                $section->removeComponent($section_component_uuid);
                drupal_set_message(t("Sanitized :block", [':block' => $section_component_uuid]));
              }
            }
          }
        }
        $section_storage->save();
      }
    }
    catch (\Exception $e) {
      drupal_set_message(t("An exception was encountered: :e", [':e' => $e->getMessage()]), 'warning');
    }

  }

}
