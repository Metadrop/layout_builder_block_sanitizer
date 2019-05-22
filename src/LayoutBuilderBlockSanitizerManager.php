<?php

namespace Drupal\layout_builder_block_sanitizer;

use Drupal\block_content\BlockContentUuidLookup;
use Drupal\layout_builder\LayoutTempstoreRepository;
use Drupal\node\Entity\Node;
use Drupal\layout_builder\SectionStorage\SectionStorageManager;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\layout_builder\LayoutEntityHelperTrait;

/**
 * Class LayoutBuilderBlockSanitizerManager.
 */
class LayoutBuilderBlockSanitizerManager {

  use LayoutEntityHelperTrait;

  /**
   * Drupal\block_content\BlockContentUuidLookup definition.
   *
   * @var \Drupal\block_content\BlockContentUuidLookup
   */
  protected $blockContentUuidLookup;

  /**
   * Drupal\layout_builder\SectionStorage\SectionStorageManager definition.
   *
   * @var \Drupal\layout_builder\SectionStorage\SectionStorageManager
   */
  protected $pluginManagerLayoutBuilderSectionStorage;

  /**
   * Drupal\layout_builder\LayoutTempstoreRepository definition.
   *
   * @var \Drupal\layout_builder\LayoutTempstoreRepository
   */
  protected $layoutBuilderTempstoreRepository;

  /**
   * Constructs a new LayoutBuilderBlockSanitizerManager object.
   */
  public function __construct(BlockContentUuidLookup $block_content_uuid_lookup, SectionStorageManager $plugin_manager_layout_builder_section_storage, LayoutTempstoreRepository $layout_builder_tempstore_repository) {
    $this->blockContentUuidLookup = $block_content_uuid_lookup;
    $this->pluginManagerLayoutBuilderSectionStorage = $plugin_manager_layout_builder_section_storage;
    $this->layoutBuilderTempstoreRepository = $layout_builder_tempstore_repository;
  }

  /**
   * Helper callback to get list of nodes.
   *
   * @todo: Get only LB enabled node types?
   */
  public function getNodes() {
    return Node::loadMultiple();
  }

  /**
   * Sanitize a node by ID.
   */
  public function sanitizeNode($nid_to_sanitize) {
    try {
      // Load node objet to sanitize.
      $entity = Node::load($nid_to_sanitize);
      $type = 'overrides';
      $contexts['entity'] = EntityContext::fromEntity($entity);
      $view_mode = 'full';
      $view_mode = LayoutBuilderEntityViewDisplay::collectRenderDisplay($entity, $view_mode)->getMode();
      $contexts['view_mode'] = new Context(new ContextDefinition('string'), $view_mode);
      $section_storage = $this->pluginManagerLayoutBuilderSectionStorage->load($type, $contexts);
      $section_storage = $this->layoutBuilderTempstoreRepository->get($section_storage);
      $id = $section_storage->getStorageId();
      $sections = $section_storage->getSections();
      // Check through each section's components to confirm blocks are valid.
      foreach ($sections as $key => &$section) {
        $components = $section->getComponents();
        foreach ($components as $section_component_uuid => $section_component) {
          $configuration = $section_component->get('configuration');
          $provider = $configuration['provider'] ?? '';
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

      // Sanitize default display.
      $section_storage = $this->getSectionStorageForEntity($entity);
      $sections = $section_storage->getSections();
      foreach ($sections as $key => &$section) {
        $components = $section->getComponents();
        foreach ($components as $section_component_uuid => $section_component) {
          $configuration = $section_component->get('configuration');
          $provider = $configuration['provider'] ?? '';
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
    catch (\TypeError $type_error) {
      // @todo Figure out why type error is thrown, take appropriate action.
    }
    catch (\Exception $e) {
      drupal_set_message(t("An exception was encountered: :e", [':e' => $e->getMessage()]), 'warning');
    }
  }

}
