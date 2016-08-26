<?php

namespace Drupal\comment_limit;

/**
 * Class CommentLimitQuery.
 *
 * @package Drupal\comment_limit
 */
class CommentLimitQuery {

  /** @var  $entity_id */
  protected $entity_id;

  /**
   * Constructor.
   */
  public function __construct($entity_id) {
    $this->entity_id = $entity_id;
  }
  /**
   * Get user comment limit for this node type.
   */
  function comment_limit_get_user() {
    $user = \Drupal::currentUser();
    $uid = $user->id();
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
