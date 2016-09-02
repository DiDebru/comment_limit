<?php

namespace Drupal\comment_limit;


use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountProxyInterface;

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
   */
  public function getUserLimit($entity_id) {
    // Count comment of user.
    $query = $this->database->select('comment_field_data', 'c')
      ->fields('c', ['entity_id', 'uid'])
      ->condition('uid', $this->user->id())
      ->condition('entity_id', $entity_id)
      ->execute();
    $query->allowRowCount = TRUE;
    return $query->rowCount();
  }

  /**
   * Get node comment limit for this entity.
   */
  public function getEntityLimit($entity_id) {
    $query = $this->database->select('comment_entity_statistics', 'c')
      ->fields('c', ['comment_count'])
      ->condition('entity_id', $entity_id)
      ->execute()
      ->fetchField();
    return $query;
  }

}
