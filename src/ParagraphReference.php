<?php

namespace Drupal\csp_cache;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\ParagraphInterface;


/**
 * Provides a ParagraphReference service.
 */
class ParagraphReference {

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a ParagraphReference class.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity field manager service.
   */
  public function __construct(EntityFieldManagerInterface $entity_field_manager) {
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * Get cachetags for nodes references by paragraphs.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to get paragraph references from.
   *
   * @return array
   *   A list of cache tags to add for this node.
   */
  public function getParagraphCacheTags(NodeInterface $node) {
    $cachetags = [];
    // Get paragraph fields on this node.
    $paragraphs = $this->getParagraphFields($node);
    foreach ($paragraphs as $field_name) {
      $paragraph_values = $node->get($field_name)->referencedEntities();
      if ($paragraph_values) {
        foreach ($paragraph_values as $paragraph) {
          // Get entity reference fields on each paragraph.
          $references = $this->getEntityReferenceFields($paragraph);
          if ($references) {
            // If entity reference links to a node, add it to cachetags.
            foreach ($references as $reference_field) {
              $referenced_values = $paragraph->get($reference_field)->referencedEntities();
              if ($referenced_values) {
                foreach ($referenced_values as $reference) {
                  if ($reference instanceof NodeInterface) {
                    $cachetags[] = 'node:' . $reference->id();
                  }
                }
              }
            }
          }
        }
      }

    }
    return $cachetags;
  }

  /**
   * Get paragraph fieldnames on the provided node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to check for paragraph fields.
   *
   * @return array
   *   List of paragraph field names.
   */
  protected function getParagraphFields(NodeInterface $node) {
    return $this->getFieldsOfType($node, 'entity_reference_revisions');
  }

  /**
   * Get entity_reference fieldnames on a given Paragraph entity.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph entity to check.
   *
   * @return array
   *   List of paragraph field names.
   */
  protected function getEntityReferenceFields(ParagraphInterface $paragraph) {
    return $this->getFieldsOfType($paragraph, 'entity_reference');
  }

  /**
   * Get fieldnames of a specific type from a given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   * @param $type
   *   The field type to find.
   *
   * @return array
   *   List of fieldnames.
   */
  protected function getFieldsOfType(EntityInterface $entity, $type) {
    $fieldnames = [];
    // Get fields of the provided type.
    $type_fields = $this->entityFieldManager
      ->getFieldMapByFieldType($type);
    // Get all fields on this entity bundle.
    $field_definitions = $this->entityFieldManager
      ->getFieldDefinitions($entity->getEntityTypeId(), $entity->bundle());
    // Get all fieldnames that are the provided type on this entity bundle.
    foreach ($field_definitions as $field_name => $field_definition) {
      if ($field_definition instanceof FieldConfig) {
        if (in_array($field_name, array_keys($type_fields[$entity->getEntityTypeId()]))) {
          $fieldnames[] = $field_name;
        }
      }
    }
    return $fieldnames;
  }

}
