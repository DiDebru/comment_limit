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
   * FieldConfig of the current comment field.
   *
   * @var FieldConfig $comment
   */
  protected $comment;

  /**
   * Constructor.
   */
  public function __construct(Connection $database, AccountProxyInterface $user, FieldConfig $comment) {
    $this->database = $database;
    $this->user = $user;
    $this->comment = $comment;
  }

  /**
   * Get user comment limit for this user.
   *
   * @return int
   *    Current count of comments the user has made on an entity.
   */
  public function getCurrentCommentCountForUser($entityId) {
    // Count comment of user.
    $query = $this->database->select('comment_field_data', 'c')
      ->fields('c', ['entity_id', 'uid'])
      ->condition('uid', $this->user->id())
      ->condition('entity_id', $entityId)
      ->execute();
    $query->allowRowCount = TRUE;
    return $query->rowCount();
  }

  /**
   * Get node comment limit for this entity.
   *
   * @return int
   *    Current count of comments that were made on an entity.
   */
  public function getCurrentCommentsOnEntity($entityId) {
    $query = $this->database->select('comment_entity_statistics', 'c')
      ->fields('c', ['comment_count'])
      ->condition('entity_id', $entityId)
      ->execute()
      ->fetchField();
    return $query;
  }

  /**
   * Get the comment limit of the entity.
   *
   * @return mixed|null
   *   Returns the comment limit of the entity.
   */
  public function getEntityLimit() {
    return $this->comment->getThirdPartySetting('comment_limit', 'edit-limit-per-entity', FALSE);
  }

  /**
   * Get the comment limit for the user.
   *
   * @return mixed|null
   *   Returns the comment limit for the user.
   */
  public function getUserLimit() {
    return $this->comment->getThirdPartySetting('comment_limit', 'edit-limit-per-user', FALSE);
  }

  /**
   * Has the user reached his/her comment limit.
   *
   * @param int $entityId
   *   The ID of the current entity.
   *
   * @return bool
   *    Returns TRUE or FALSE.
   */
  public function hasUserLimitReached($entityId) {
    if ($this->getCurrentCommentCountForUser($entityId) <= $this->getUserLimit() && $this->user->hasPermission('bypass comment limit')) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Has the comment limit for the entity been reached.
   *
   * @param int $entityId
   *    The ID of the current entity.
   *
   * @return bool
   *    Returns TRUE or FALSE.
   */
  public function isEntityLimitReached($entityId) {
    if ($this->getCurrentCommentsOnEntity($entityId) <= $this->getEntityLimit() && $this->user->hasPermission('bypass comment limit')) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

}
