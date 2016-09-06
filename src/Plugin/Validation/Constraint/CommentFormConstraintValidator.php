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
    if ($entity) {
      $entity_id = $entity->id();
      $entity_type = $entity->getEntityTypeId();
      if (
        $this->commentLimit->getUserLimit($entity_id, $entity_type) &&
        $this->commentLimit->getEntityLimit($entity_id, $entity_type)
      ) {
        if (
          $this->commentLimit->hasEntityLimitReached($entity_id, $entity_type) &&
          $this->commentLimit->hasUserLimitReached($entity_id, $entity_type)
        ) {
          // The maximum of %comments is reached for you and this %bundle.
          return;
        }
      }
      if ($this->commentLimit->getEntityLimit($entity_id, $entity_type)) {
        if ($this->commentLimit->hasEntityLimitReached($entity_id, $entity_type)) {
          $form = [];
          $form['error_node'] = [
            '#type' => 'html_tag',
            '#tag' => 'p',
            '#value' => t('The comment limit for this @entity was reached', ['@entity' => $entity->bundle()]),
          ];
          return $form;
        }
      }
      if ($this->commentLimit->getUserLimit($entity_id, $entity_type)) {
        if ($this->commentLimit->hasUserLimitReached($entity_id, $entity_type)) {
          $form = [];
          $form['error_user'] = [
            '#type' => 'html_tag',
            '#tag' => 'p',
            '#value' => t('You have reached your comment limit for this @entity', ['@entity' => $entity->bundle()]),
          ];
          return $form;
        }
      }
    }
  }

}
