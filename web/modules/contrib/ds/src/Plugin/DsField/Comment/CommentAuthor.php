<?php

namespace Drupal\ds\Plugin\DsField\Comment;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ds\Attribute\DsField;
use Drupal\ds\Plugin\DsField\Field;

/**
 * Plugin that renders the author of a comment.
 */
#[DsField(
  id: 'comment_author',
  title: new TranslatableMarkup('Author'),
  entity_type: 'comment',
  provider: 'comment'
)]
class CommentAuthor extends Field {

  /**
   * {@inheritdoc}
   */
  protected function entityRenderKey() {
    return 'name';
  }

}
