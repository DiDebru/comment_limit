<?php

namespace Drupal\comment_limit;
use Drupal\Core\Session\AccountInterface;

/**
 * Class CommentLimitQuery.
 *
 * @package Drupal\comment_limit
 */
class CommentLimitQuery {

  /** @var  $entity_id */
  protected $entity_id;

  /** @var  $user */
  protected $user;

  /**
   * Constructor.
   */
  public function __construct($entity_id, AccountInterface $user) {
    $this->entity_id = $entity_id;
    $this->user = $user;
  }
  /**
   * Get user comment limit for this node type.
   */
  function comment_limit_get_user() {
    $uid = $this->user->id();
    // Count comment of user.
    $db = \Drupal::database();
    $query = $db->select('comment_field_data', 'c')
      ->fields('c', ['entity_id', 'uid'])
      ->condition('uid', $uid)
      ->condition('entity_id', $this->entity_id)
      ->execute();
    $query->allowRowCount = TRUE;
    return $query->rowCount();
  }

  /**
   * Get node comment limit for this node type.
   */
  function comment_limit_get_node() {
    $db = \Drupal::database();
    $query = $db->select('comment_entity_statistics', 'c')
      ->fields('c', ['comment_count'])
      ->condition('entity_id', $this->entity_id)
      ->execute()
      ->fetchField();
    return $query;
  }

}
