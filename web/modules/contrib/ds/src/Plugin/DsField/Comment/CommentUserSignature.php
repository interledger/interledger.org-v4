<?php

namespace Drupal\ds\Plugin\DsField\Comment;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ds\Attribute\DsField;
use Drupal\ds\Plugin\DsField\User\UserSignature;
use Drupal\user\Entity\User;

/**
 * Plugin that renders the user signature of a comment.
 */
#[DsField(
  id: 'comment_user_signature',
  title: new TranslatableMarkup('User signature'),
  entity_type: 'comment',
  provider: 'comment'
)]
class CommentUserSignature extends UserSignature {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $comment = $this->entity();
    $user_id = $comment->uid->target_id;
    $user = User::load($user_id);

    $key = $this->key();
    if (isset($user->{$key}->value)) {
      $format = $this->format();
      return [
        '#type' => 'processed_text',
        '#text' => $user->{$key}->value,
        '#format' => $user->{$format}->value,
        '#filter_types_to_skip' => [],
        '#langcode' => '',
      ];
    }

    return [];
  }

}
