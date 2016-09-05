<?php

namespace Drupal\comment_limit\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Supports validating comment form.
 *
 * @Constraint(
 * id = "CommentFormConstraint",
 * label = @Translation("CommentFormConstraint", context = "Validation")
 * )
 */
class CommentFormConstraint extends Constraint {

  public $message = 'The comment limit was reached.';

  /**
   * Context.
   *
   * @var \Symfony\Component\Validator\ExecutionContextInterface
   */
  protected $context;

  /**
   * {@inheritdoc}
   */
  public function initialize(ExecutionContextInterface $context) {
    $this->context = $context;
  }

  /**
   * {@inheritdoc}
   */
  public function validatedBy() {
    return get_class($this);
  }

  /**
   * Validate user and entity limits for comments.
   */
  public function validate() {
    $commentLimit = \Drupal::service('comment_limit.service');
    $entityId = $commentLimit->getEntityId();
    $entityType = $commentLimit->getEntityType();
    if ($entityId && $entityType) {

      $limitEntity = TRUE;
      $limitUser = TRUE;

      if ($commentLimit->hasEntityLimitReached($entityId, $entityType)) {
        $limitEntity = FALSE;
      }

      if ($commentLimit->hasUserLimitReached($entityId, $entityType)) {
        $limitUser = FALSE;
      }

      if (!$limitEntity || !$limitUser) {
        $this->context->addViolation($this->message);
      }
    }
  }

}
