<?php

namespace Drupal\comment_limit;

use Drupal\Core\Database\Connection;
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
    return $commentLimit->getThirdPartySetting('comment_limit', 'edit-limit-per-entity', FALSE);
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
    return $this->getFieldConfig($entity_id, $entity_type)->getThirdPartySetting('comment_limit', 'edit-limit-per-user', FALSE);
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
    $test1 = $this->getCurrentCommentCountForUser($entity_id, $entity_type);
    $test3 = $this->getUserLimit($entity_id, $entity_type);
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
    $test = $this->getCurrentCommentsOnEntity($entity_id, $entity_type);
    $test2 = $this->getEntityLimit($entity_id, $entity_type);
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

}
