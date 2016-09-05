<?php

namespace Drupal\comment_limit;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\field\Entity\FieldConfig;

/**
 * Class CommentLimitQuery.
 *
 * @package Drupal\comment_limit
 */
class CommentLimit {

  /**
   * The user object.
   *
   * @var AccountProxyInterface $user
   */
  protected $user;

  /**
   * Database connection.
   *
   * @var Connection $database
   */
  protected $database;

  /**
   * Entity Id.
   *
   * @var int $entityId
   */
  protected $entityId;

  /**
   * Entity type.
   *
   * @var string $entityType
   */
  protected $entityType;

  /**
   * Constructor.
   */
  public function __construct(Connection $database, AccountProxyInterface $user) {
    $this->database = $database;
    $this->user = $user;
  }

  /**
   * Get user comment limit for this user.
   *
   * @param int $entity_id
   *   The ID of the current entity.
   * @param string $entity_type
   *   Current entity type.
   *
   * @return int
   *    Current count of comments the user has made on an entity.
   */
  public function getCurrentCommentCountForUser($entity_id, $entity_type) {
    // Count comment of user.
    $query = $this->database->select('comment_field_data', 'c')
      ->fields('c', ['entity_id', 'uid'])
      ->condition('uid', $this->user->id())
      ->condition('entity_id', $entity_id)
      ->condition('entity_type', $entity_type)
      ->execute();
    $query->allowRowCount = TRUE;
    return $query->rowCount();
  }

  /**
   * Get node comment limit for this entity.
   *
   * @param int $entity_id
   *   The ID of the current entity.
   * @param string $entity_type
   *   Current entity type.
   *
   * @return int
   *    Current count of comments that were made on an entity.
   */
  public function getCurrentCommentsOnEntity($entity_id, $entity_type) {
    $query = $this->database->select('comment_entity_statistics', 'c')
      ->fields('c', ['comment_count'])
      ->condition('entity_id', $entity_id)
      ->condition('entity_type', $entity_type)
      ->execute()
      ->fetchField();
    return $query;
  }

  /**
   * Get the comment limit of the entity.
   *
   * @param int $entity_id
   *   The ID of the current entity.
   * @param string $entity_type
   *   Current entity type.
   *
   * @return mixed|null
   *   Returns the comment limit of the entity.
   */
  public function getEntityLimit($entity_id, $entity_type) {
    $commentLimit = $this->getFieldConfig($entity_id, $entity_type);
    return $commentLimit->getThirdPartySetting('comment_limit', 'entity_limit', FALSE);
  }

  /**
   * Get the comment limit for the user.
   *
   * @param int $entity_id
   *   The ID of the current entity.
   * @param string $entity_type
   *   Current entity type.
   *
   * @return mixed|null
   *   Returns the comment limit for the user.
   */
  public function getUserLimit($entity_id, $entity_type) {
    return $this->getFieldConfig($entity_id, $entity_type)->getThirdPartySetting('comment_limit', 'user_limit', FALSE);
  }

  /**
   * Has the user reached his/her comment limit.
   *
   * @param int $entity_id
   *   The ID of the current entity.
   * @param string $entity_type
   *   Current entity type.
   *
   * @return bool
   *    Returns TRUE or FALSE.
   */
  public function hasUserLimitReached($entity_id, $entity_type) {
    if ($this->getCurrentCommentCountForUser($entity_id, $entity_type) >= $this->getUserLimit($entity_id, $entity_type) && !$this->user->hasPermission('bypass comment limit')) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Has the comment limit for the entity been reached.
   *
   * @param int $entity_id
   *    The ID of the current entity.
   * @param string $entity_type
   *   Current entity type.
   *
   * @return bool
   *    Returns TRUE or FALSE.
   */
  public function hasEntityLimitReached($entity_id, $entity_type) {
    if ($this->getCurrentCommentsOnEntity($entity_id, $entity_type) >= $this->getEntityLimit($entity_id, $entity_type) && !$this->user->hasPermission('bypass comment limit')) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Get the FieldConfig of a comment field used in a specific entity bundle.
   *
   * @param int $entity_id
   *   Current entity id.
   * @param string $entity_type
   *   Current entity type.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null|static
   *    Returns the FieldConfig object.
   */
  private function getFieldConfig($entity_id, $entity_type) {
    $entity = \Drupal::entityTypeManager()->getStorage($entity_type)->load($entity_id);
    $entity_bundle = $entity->bundle();
    $field_config = FieldConfig::load($entity_type . '.' . $entity_bundle . '.comment');
    return $field_config;
  }

  /**
   * Set the entity id.
   *
   * @param int $entityId
   *    The current entity id called in hook_form_FORM_ID_alter().
   */
  public function setEntityId($entityId) {
    $this->entityId = $entityId;
  }

  /**
   * Set the entity type.
   *
   * @param string $entityType
   *    The current entity type called in hook_form_FORM_ID_alter().
   */
  public function setEntityType($entityType) {
    $this->entityType = $entityType;
  }

  /**
   * Get the entity id.
   *
   * @return int entityId
   *   Get the entity id called in hook_form_FORM_ID_alter().
   */
  public function getEntityId() {
    return $this->entityId;
  }

  /**
   * Get the entity type.
   *
   * @return string entity type
   *    Get the entity type called in hook_form_FORM_ID_alter().
   */
  public function getEntityType() {
    return $this->entityType;
  }

  /**
   * Get all ContentEntityTypes.
   *
   * @return array entity types
   *    Get an array of all ContentEntities.
   */
  public function getAllEntityTypes() {
    // Get all entities.
    $entity_types = \Drupal::entityTypeManager()->getDefinitions();
    $content_entity_types = array_filter($entity_types, function ($entity_type) {
      return $entity_type instanceof ContentEntityTypeInterface;
    });
    $content_entity_type_ids = array_keys($content_entity_types);
    return $content_entity_type_ids;
  }

}
