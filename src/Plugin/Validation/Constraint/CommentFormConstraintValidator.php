<?php

namespace Drupal\comment_limit\Plugin\Validation\Constraint;

use Drupal\comment_limit\CommentLimit;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\ExecutionContextInterface;

/**
 * Class CommentFormConstraintValidator.
 *
 * @package Drupal\comment_limit\Plugin\Validation\Constraint
 */
class CommentFormConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

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
    var_dump($entity);

    if ($constraint->entityType && $constraint->entityId) {
      $entity_id = $constraint->entityId;
      $entity_type = $constraint->entityType;
      if (
        $this->commentLimit->getUserLimit($entity_id, $entity_type) ||
        $this->commentLimit->getEntityLimit($entity_id, $entity_type)
      ) {
        if (
          $this->commentLimit->hasEntityLimitReached($entity_id, $entity_type) ||
          $this->commentLimit->hasUserLimitReached($entity_id, $entity_type)
        ) {
          return $this->context->addViolation(t('The comment limit was reached for @type/@id', ['@type' => $entity_type, '@id' => $entity_id]));
        }
      }
    }
  }

}
