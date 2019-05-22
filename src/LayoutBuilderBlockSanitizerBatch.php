<?php

namespace Drupal\layout_builder_block_sanitizer;

/**
 * Class LayoutBuilderBlockSanitizerBatch.
 *
 * Provides batch functionality for processing.
 */
class LayoutBuilderBlockSanitizerBatch {

  /**
   * Kick off batch process to sanitize all nodes on site.
   */
  public function batchSanitizeAllNodesStart() {
    $nodes = \Drupal::service('layout_builder_block_sanitizer.manager')->getNodes();
    $nids = array_keys($nodes);
    batch_set([
      'title' => t('Sanitizing nodes'),
      'init_message' => t('Beginning node sanitize'),
      'operations' => [
        [
          ['Drupal\layout_builder_block_sanitizer\LayoutBuilderBlockSanitizerBatch', 'batchSanitizeAllNodes'],
          [$nids],
        ],
      ],
      'finished' => ['Drupal\layout_builder_block_sanitizer\LayoutBuilderBlockSanitizerBatch', 'batchCompleted'],
    ]);
  }

  /**
   * Load nodes in batch process progressively to sanitize.
   */
  public static function batchSanitizeAllNodes($nids, &$context) {
    // Use the $context['sandbox'] at your convenience to store the
    // information needed to track progression between successive calls.
    if (empty($context['sandbox'])) {
      // Flush caches to avoid false positives looking for block UUID.
      drupal_flush_all_caches();
      $context['sandbox'] = [];
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['current_node'] = 0;
      // Save node count for the termination message.
      $context['sandbox']['max'] = count($nids);
    }
    $limit = 5;
    // Retrieve the next group.
    $current_node = $context['sandbox']['current_node'];
    $limit_node = $context['sandbox']['current_node'] + $limit;
    $result = range($current_node, $limit_node);
    foreach ($result as $row) {
      \Drupal::service('layout_builder_block_sanitizer.manager')->sanitizeNode($nids[$row]);
      $operation_details = t('Sanitizing NIDs :current to :limit', [
        ':current' => $current_node,
        ':limit' => $limit_node,
      ]);
      // Store some results for post-processing in the 'finished' callback.
      // The contents of 'results' will be available as $results in the
      // 'finished' function (in this example, batch_example_finished()).
      $context['results'][] = $row;

      // Update our progress information.
      $context['sandbox']['progress']++;
      $context['sandbox']['current_node'] = $row;
      $context['message'] = t('@details', [
        '@details' => $operation_details,
      ]);
    }

    // Inform the batch engine that we are not finished,
    // and provide an estimation of the completion level we reached.
    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] >= $context['sandbox']['max'];
    }
  }

  /**
   * Callback for batch processing completed.
   */
  public static function batchCompleted($success, $results, $operations) {
    $messenger = \Drupal::messenger();
    if ($success == TRUE) {
      $messenger
        ->addMessage(t('@count nodes were sanitized.', [
          '@count' => count($results),
        ]));
    }
    else {
      // An error occurred.
      $messenger
        ->addMessage(t('An error occurred while processing.'));
    }
  }

}
