<?php

namespace Drupal\comment_limit\Plugin\Validation\Constraint;

use Drupal\comment_limit\CommentLimit;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\ExecutionContextInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class CommentFormConstraintValidator.
 *
 * @package Drupal\comment_limit\Plugin\Validation\Constraint
 */
class CommentFormConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * Validator 2.5 and upwards compatible execution context.
   *
   * @var ExecutionContextInterface
   */
  protected $context;

  /**
   * Inject comment_limit.service.
   *
   * @var CommentLimit $commentLimit
   */
  protected $commentLimit;

  /**
   * Constructs a new CommentFormConstraintValidator.
   *
   * @param CommentLimit $comment_limit
   *   The comment_limit.service.
   */
  public function __construct(CommentLimit $comment_limit) {
    $this->commentLimit = $comment_limit;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('comment_limit.service'));
  }

  /**
   * {@inheritdoc}
   */
  public function initialize(ExecutionContextInterface $context) {
    $this->context = $context;
  }

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    if ($constraint->entityType && $constraint->entityId && $constraint->fieldId) {
      $entity_id = $constraint->entityId;
      $entity_type = $constraint->entityType;
      $field_id = $constraint->fieldId;
      $field_name = $constraint->fieldName;

      if ($this->commentLimit->getFieldLimit($field_id) > 0) {
        if ($this->commentLimit->hasFieldLimitReached($entity_id, $entity_type, $field_name, $field_id)) {
          return $this->context->addViolation($this->t('The comment limit was reached for @field', ['@field' => $field_id]));
        }
      }
      if ($this->commentLimit->getUserLimit($field_id) > 0) {
        if ($this->commentLimit->hasUserLimitReached($entity_id, $entity_type, $field_name, $field_id)) {
          return $this->context->addViolation($this->t('The comment limit was reached for @id', ['@id' => $entity_id]));
        }
      }
    }

  }

}
