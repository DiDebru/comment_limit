<?php

namespace Drupal\comment_limit\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Supports validating comment form.
 *
 * @Constraint(
 * id = "CommentFormConstraint",
 * label = @Translation("CommentFormConstraint", context = "Validation")
 * )
 */
class CommentFormConstraint extends Constraint {

  public $entityId;

  public $entityType;

  public $bundle;

}
