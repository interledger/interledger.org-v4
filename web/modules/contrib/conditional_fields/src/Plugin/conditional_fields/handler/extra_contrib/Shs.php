<?php

namespace Drupal\conditional_fields\Plugin\conditional_fields\handler\extra_contrib;

use Drupal\conditional_fields\Plugin\conditional_fields\handler\Select;

/**
 * Provides states handler for multiple select lists.
 *
 * Multiple select fields always require an array as value.
 * In addition, since our modified States API triggers a dependency only if all
 * reference values of type Array are selected, a different selector must be
 * added for each value of a set for OR, XOR and NOT evaluations.
 *
 * @ConditionalFieldsHandler(
 *   id = "states_handler_options_shs",
 * )
 */
class Shs extends Select {

}
