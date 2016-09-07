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
   * Message to show.
   *
   * @var string $message
   */
  protected $message;

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
  public function getUserLimit($field_id) {
    $commentLimit = $this->getFieldConfig($field_id);
    return $commentLimit->getThirdPartySetting('comment_limit', 'user_limit', FALSE);
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
  public function hasFieldLimitReached($entity_id, $entity_type, $field_id) {
    if ($this->getCurrentCommentsOnEntity($entity_id, $entity_type) >= $this->getFieldLimit($field_id) && !$this->user->hasPermission('bypass comment limit')) {
      return TRUE;
    }
    else {
      return FALSE;
    }
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

  /**
   * Get the right error message.
   *
   * @param int $entity_id
   *   The entity id.
   * @param string $entity_type
   *   The entity type.
   * @param string $bundle
   *   The bundle.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Returns translateable markup with the correct error message.
   */
  public function getMessage($entity_id, $entity_type, $bundle) {
    if (
      $this->getUserLimit($entity_id, $entity_type) &&
      $this->getEntityLimit($entity_id, $entity_type)
    ) {
      if (
        $this->hasEntityLimitReached($entity_id, $entity_type) &&
        $this->hasUserLimitReached($entity_id, $entity_type)
      ) {
        return $this->message = t('The comment limits for this @entity and your limit were reached', ['@entity' => $bundle]);
      }
    }
    if ($this->getEntityLimit($entity_id, $entity_type)) {
      if ($this->hasEntityLimitReached($entity_id, $entity_type)) {
        return $this->message = t('The comment limit for this @entity was reached', ['@entity' => $bundle]);
      }
    }
    if ($this->getUserLimit($entity_id, $entity_type)) {
      if ($this->hasUserLimitReached($entity_id, $entity_type)) {
        return $this->message = t('You have reached your comment limit for this @entity', ['@entity' => $bundle]);
      }
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
    $fields = $entity->getFieldDefinitions();
    $field_names_as_keys = array_keys($fields);
    $field_names = preg_grep('/comment/', $field_names_as_keys);
    $field_name = array_values($field_names);
    $field_config = FieldConfig::loadByName($entity_type, $entity_bundle, $field_name[0]);

    return $field_config;
  }

}
