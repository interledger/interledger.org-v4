(($, Drupal) => {
  /**
   * Enhancements to states.js.
   */
  // Checking if autocomplete is plugged in.
  if (Drupal.autocomplete) {
    /**
     * Handles an autocompleteselect event.
     *
     * Override the autocomplete method to add a custom event.
     *
     * @param {jQuery.Event} event
     *   The event triggered.
     * @param {object} ui
     *   The jQuery UI settings object.
     *
     * @return {bool}
     *   Returns false to indicate the event status.
     */
    Drupal.autocomplete.options.select = function selectHandler(event, ui) {
      const terms = Drupal.autocomplete.splitValues(event.target.value);
      // Remove the current input.
      terms.pop();
      // Add the selected item.
      if (ui.item.value.search(',') > 0) {
        terms.push(`"${ui.item.value}"`);
      } else {
        terms.push(ui.item.value);
      }
      event.target.value = terms.join(', ');
      // Fire custom event that other controllers can listen to.
      jQuery(event.target).trigger('autocomplete-select');
      // Return false to tell jQuery UI that we've filled in the value already.
      return false;
    };
  }

  /**
   * New and existing states enhanced with configurable options.
   * Event names of states with effects have the following structure:
   * state:stateName-effectName.
   */

  // Visible/Invisible.
  $(document)
    .on('state:visible-fade', (e) => {
      if (e.trigger) {
        $(e.target)
          .closest('.form-item, .form-submit, .form-wrapper')
          [e.value ? 'fadeIn' : 'fadeOut'](e.effect.speed);
      }
    })
    .on('state:visible-slide', (e) => {
      if (e.trigger) {
        $(e.target)
          .closest('.form-item, .form-submit, .form-wrapper')
          [e.value ? 'slideDown' : 'slideUp'](e.effect.speed);
      }
    })
    // Empty/Filled.
    .on('state:empty', (e) => {
      if (e.trigger) {
        const fields = $(e.target).find('input, select, textarea');
        fields.each(function processControls() {
          if (
            typeof $(this).data('conditionalFieldsSavedValue') === 'undefined'
          ) {
            $(this).data('conditionalFieldsSavedValue', this.value);
          }
          if (e.effect) {
            if (e.value) {
              this.value = e.effect.value;
            } else if ($(this).data('conditionalFieldsSavedValue')) {
              this.value = $(this).data('conditionalFieldsSavedValue');
            }
          }
        });
      }
    })
    // On invisible make empty and unrequired.
    .on('state:visible', (e) => {
      if (e.trigger) {
        const fields = $(e.target).find('input, select, textarea');
        fields.each(function processControls() {
          const $field = $(this);
          // Save required property.
          if (
            typeof $field.data('conditionalFieldsSavedRequired') === 'undefined'
          ) {
            $field.data(
              'conditionalFieldsSavedRequired',
              $field.attr('required'),
            );
          }
          // Go invisible.
          if (!e.value) {
            // Remove required property.
            $field.trigger({
              type: 'state:required',
              value: false,
              trigger: true,
            });
          }
          // Go visible.
          else {
            // Restore required if necessary.
            // eslint-disable-next-line no-lonely-if
            if ($field.data('conditionalFieldsSavedRequired')) {
              $field.trigger({
                type: 'state:required',
                value: true,
                trigger: true,
              });
            }
          }
        });
      }
    })
    // Required/Not-Required.
    .on('state:required', (e) => {
      if (e.trigger) {
        const fieldsSupportingRequired = $(e.target).find('input, textarea');
        const legends = $(e.target).find('legend');
        const legendsspan = $(e.target).find('legend span');
        const labels = $(e.target).find(
          ':not(.form-item--editor-format, .form-type-radio)>label',
        );
        const tabs = $('.vertical-tabs');
        let tab = '';
        if (tabs.length !== 0) {
          const detail = $(legends).closest('details');
          const selector = `a[href='#${detail.attr('id')}']`;
          tab = $(selector);
        }
        if (e.value) {
          if (legends.length !== 0) {
            legends.addClass('form-required');
            legendsspan.addClass('js-form-required form-required');
            if (tabs.length !== 0) {
              tab.find('strong').addClass('form-required');
            }
          } else {
            labels.addClass('form-required');
          }
          fieldsSupportingRequired
            .filter(`[name *= "[0]"]`)
            .attr('required', 'required');
        } else {
          if (legends.length !== 0) {
            legends.removeClass('form-required');
            legendsspan.removeClass('js-form-required form-required');
            if (tabs.length !== 0) {
              tab.find('strong').removeClass('form-required');
            }
          } else {
            labels.removeClass('form-required');
          }
          fieldsSupportingRequired.removeAttr('required');
        }
      }
    })
    // Unchanged state. Do nothing.
    .on('state:unchanged', () => {});

  Drupal.behaviors.conditionalFields = {
    attach(context, settings) {
      // AJAX is not updating settings.conditionalFields correctly.
      const conditionalFields = settings.conditionalFields || 'undefined';
      if (
        typeof conditionalFields === 'undefined' ||
        typeof conditionalFields.effects === 'undefined'
      ) {
        return;
      }
      // Override state change handlers for dependents with special effects.
      const eventsData = $.hasOwnProperty('_data')
        ? $._data(document, 'events')
        : $(document).data('events');
      $.each(eventsData, (i, events) => {
        if (i.substring(0, 6) === 'state:') {
          const originalHandler = events[0].handler;
          events[0].handler = (e) => {
            const effect = conditionalFields.effects[`#${e.target.id}`];
            if (typeof effect !== 'undefined') {
              const effectEvent = `${i}-${effect.effect}`;
              if (typeof eventsData[effectEvent] !== 'undefined') {
                $(e.target).trigger({
                  type: effectEvent,
                  trigger: e.trigger,
                  value: e.value,
                  effect: effect.options,
                });
                return;
              }
            }
            e.effect = effect;
            originalHandler(e);
          };
        }
      });
    },
  };

  Drupal.behaviors.ckeditorTextareaFix = {
    attach(context) {
      if (typeof CKEDITOR !== 'undefined') {
        // eslint-disable-next-line no-undef
        const ckEditor = CKEDITOR;
        ckEditor.on('instanceReady', () => {
          $(context)
            .find('.form-textarea-wrapper textarea')
            .each(function processTextarea() {
              const $textarea = jQuery(this);
              if (ckEditor.instances[$textarea.attr('id')] !== undefined) {
                ckEditor.instances[$textarea.attr('id')].on('change', () => {
                  ckEditor.instances[$textarea.attr('id')].updateElement();
                  $textarea.trigger('keyup');
                });
              }
            });
        });
      }
    },
  };

  Drupal.behaviors.autocompleteChooseTrigger = {
    attach(context) {
      $(context)
        .find('.form-autocomplete')
        .each(function processAutocomplete() {
          const $input = $(this);
          $(this).on('autocomplete-select', () => {
            setTimeout(() => {
              $input.trigger('keyup');
            }, 1);
          });
        });
    },
  };

  /**
   * The function for compare two strings
   * @param a
   * @param b
   * @return {boolean|*}
   * @private
   */
  function _compare2(a, b) {
    a = typeof a === 'string' ? a.replace(/(^[\n\r]+|[\n\r]+$)/g, '') : a;
    b = typeof b === 'string' ? b.replace(/(^[\n\r]+|[\n\r]+$)/g, '') : b;
    if (a === b) {
      return typeof a === 'undefined' ? a : true;
    }

    return typeof a === 'undefined' || typeof b === 'undefined';
  }

  Drupal.behaviors.statesModification = {
    weight: -10,
    attach() {
      if (Drupal.states) {
        /**
         * Handle array values.
         * @see http://drupal.org/node/1149078
         */
        Drupal.states.Dependent.comparisons.Array = (reference, value) => {
          // Make sure value is an array.
          let compare = [];
          if (typeof value === 'string') {
            compare = value.split(/\r?\n\r?/);
          } else if (typeof value === 'object' && value instanceof Array) {
            compare = value;
          }

          if (compare.length < 1) {
            return false;
          }
          // We iterate through each value provided in the reference. If all of them
          // exist in value array, we return true. Otherwise return false.
          // eslint-disable-next-line no-restricted-syntax
          for (const key in reference) {
            if (
              reference.hasOwnProperty(key) &&
              $.inArray(String(reference[key]), compare) < 0
            ) {
              return false;
            }
          }
          return true;
        };

        /**
         * Handle object values.
         */
        Drupal.states.Dependent.comparisons.Object = (reference, value) => {
          /**
           * Adds RegEx support
           * https://www.drupal.org/node/1340616
           */
          if ('regex' in reference) {
            // The fix for regex when value is array
            const regObj = new RegExp(reference.regex, reference.flags);
            if (value && value.constructor.name === 'Array') {
              // eslint-disable-next-line no-restricted-syntax
              for (const index in value) {
                if (regObj.test(value[index])) {
                  return true;
                }
              }
              return false;
            }
            return regObj.test(value);

            // Adds single XOR support
          }
          if ('xor' in reference) {
            let compare = [];
            if (typeof value === 'string') {
              compare = value.split(/\r?\n\r?/);
            } else if (typeof value === 'object' && value instanceof Array) {
              compare = value;
            }
            let eqCount = 0;
            // eslint-disable-next-line no-restricted-syntax
            for (const key in reference.xor) {
              if (
                reference.xor.hasOwnProperty(key) &&
                $.inArray(reference.xor[key], compare) >= 0
              ) {
                eqCount += 1;
              }
            }
            return eqCount % 2 === 1;
          }
          return reference.indexOf(value) !== false;
        };
        // The fix for compare strings wrapped by control symbols
        Drupal.states.Dependent.comparisons.String = (reference, value) => {
          if (value && value.constructor.name === 'Array') {
            // eslint-disable-next-line no-restricted-syntax
            for (const index in value) {
              if (_compare2(reference, value[index])) {
                return true;
              }
            }
            return false;
          }
          return _compare2(reference, value);
        };
      }
    },
  };
})(jQuery, Drupal);
