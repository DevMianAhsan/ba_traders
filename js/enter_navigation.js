/**
 * Global Enter Key Navigation System for Chairman ERP / POS
 * 
 * Features:
 * 1. Navigate forward with Enter key across inputs, selects, textareas, and buttons across the whole software.
 * 2. Navigate backward with Shift+Enter.
 * 3. Dropdowns (Native <select> and Select2):
 *    - On Select2:
 *      * Enter on empty Select2 opens the searchable dropdown.
 *      * Selecting a product / customer automatically advances focus to the next field (e.g. Rate/Quantity).
 *      * Enter on an already-selected Select2 box immediately moves focus to the next field (never stays stuck).
 *    - On Native <select>:
 *      * Enter on empty select opens the native picker (showPicker).
 *      * Enter on selected option or closed select moves focus to the next field.
 * 4. Date & Time Inputs:
 *    - Enter on date input opens native calendar picker (showPicker).
 *    - Next Enter advances to the next field.
 * 5. Action Buttons (e.g. #addProductPurchase, addItemRow):
 *    - Enter triggers the action (+ Add line item).
 *    - After adding, focus automatically returns to the product code/name input for rapid next entry.
 * 6. Submit Buttons:
 *    - Enter on a submit button (or at end of form) submits the form.
 * 7. Prevents unintentional form submits on Enter inside input fields.
 * 8. Supports modals (scopes navigation inside open modal).
 * 9. Preserves Alt+Enter, Alt+S, Alt+P shortcuts.
 */

(function($) {
  'use strict';

  // Check if an element is inside an active modal
  function getNavigationScope($el) {
    var $modal = $el.closest('.modal.show, .modal:visible');
    if ($modal.length) return $modal;
    var $form = $el.closest('form');
    if ($form.length) return $form;
    return $el.closest('.card-body, .card, .wrapper, body');
  }

  // Determine if a button is an action/submit button that should participate in Enter navigation
  function isActionOrSubmitButton($btn) {
    if (!$btn.length) return false;

    // Explicitly allowed key buttons
    if ($btn.is('#addProductPurchase, #sale_order_btn, #formData_btn, #formData1_btn, #voucher_general_btn, #voucher_expense_btn, #voucher_single_btn, #btn_save_sale, #btn_save_and_print_sale, [onclick*="addItemRow"], [onclick*="addInvoiceRow"], [onclick*="addpoDetails"], .btn-enter-next')) {
      return true;
    }

    // Skip dismiss / close buttons
    if ($btn.hasClass('close') || $btn.is('[data-dismiss]') || $btn.is('[data-toggle="modal"]') || $btn.is('[data-target]')) {
      return false;
    }

    // Skip row delete / remove buttons
    if ($btn.is('[onclick*="removeByid"], [onclick*="removeInvoiceRow"], [onclick*="deleteData"], .text-danger, .btn-danger')) {
      return false;
    }

    // Skip secondary info/tool buttons
    if ($btn.is('[onclick*="getpoDetails"], [onclick*="generateFBRNumber"], [onclick*="toggleManualRow"], .btn-outline-secondary')) {
      return false;
    }

    // Skip buttons inside input groups unless they are primary action
    if ($btn.closest('.input-group-prepend, .input-group-append').length) {
      return false;
    }

    // Include submit buttons or primary action buttons
    if ($btn.is('button[type="submit"], input[type="submit"], .btn-admin, .btn-primary, .btn-success')) {
      return true;
    }

    return false;
  }

  // Get all interactive elements in DOM order within the current scope
  function getNavigableElements($scope) {
    var raw = $scope.find('input, select, textarea, button').get();
    var list = [];

    for (var i = 0; i < raw.length; i++) {
      var el = raw[i];
      var $el = $(el);
      var tag = el.tagName.toLowerCase();
      var type = (el.type || '').toLowerCase();

      // Skip hidden inputs and disabled elements
      if (type === 'hidden') continue;
      if ($el.prop('disabled')) continue;

      // Skip readonly inputs and textareas (auto IDs, calculated labels)
      if (($el.prop('readonly') || $el.attr('readonly')) && tag !== 'button' && tag !== 'select') {
        continue;
      }

      // Check if Select2
      var isSelect2 = $el.hasClass('select2-hidden-accessible') || $el.data('select2') || $el.hasClass('searchableSelect') || $el.hasClass('select2');
      if (!isSelect2 && el.tabIndex === -1) {
        continue;
      }

      // Check visibility
      if (isSelect2) {
        var $s2Cont = $el.next('.select2-container');
        if (!$s2Cont.length || !$s2Cont.is(':visible')) continue;
      } else {
        if (!$el.is(':visible')) continue;
      }

      // Check buttons
      if (tag === 'button' || type === 'button' || type === 'submit') {
        if (!isActionOrSubmitButton($el)) {
          continue;
        }
      }

      list.push(el);
    }
    return list;
  }

  // Safely focus an element (handles Select2 containers, auto-selects input text)
  function focusElement(target) {
    if (!target) return;
    var $t = $(target);
    var tag = target.tagName.toLowerCase();
    var isSelect2 = $t.hasClass('select2-hidden-accessible') || $t.data('select2') || $t.hasClass('searchableSelect') || $t.hasClass('select2');

    if (isSelect2) {
      var $s2 = $t.next('.select2-container');
      var $selection = $s2.find('.select2-selection');
      if ($selection.length) {
        $selection.focus();
        return;
      }
    }

    $t.focus();
    if (tag === 'input') {
      var type = (target.type || '').toLowerCase();
      if (['text', 'number', 'search', 'tel', 'url', 'password', 'email'].indexOf(type) !== -1) {
        try {
          target.select();
        } catch (e) {}
      }
    }
  }

  // Move focus forward (+1) or backward (-1)
  function moveFocus(currentEl, step) {
    step = (typeof step === 'number') ? step : 1;
    var $current = $(currentEl);

    // If focus is inside a Select2 container, resolve back to the underlying select
    if ($current.hasClass('select2-selection') || $current.closest('.select2-container').length) {
      var $linked = $current.closest('.select2-container').prev('select');
      if ($linked.length) $current = $linked;
    }

    var $scope = getNavigationScope($current);
    var navigables = getNavigableElements($scope);
    if (!navigables.length) return;

    var curIdx = navigables.indexOf($current[0]);
    if (curIdx === -1 && $current.attr('id')) {
      for (var i = 0; i < navigables.length; i++) {
        if (navigables[i].id === $current.attr('id')) {
          curIdx = i;
          break;
        }
      }
    }

    var nextIdx = curIdx + step;
    if (nextIdx >= 0 && nextIdx < navigables.length) {
      focusElement(navigables[nextIdx]);
    } else if (step > 0 && nextIdx >= navigables.length) {
      // If at end of form, focus submit / save button if present
      var $sub = $scope.find('button[type="submit"]:visible, #sale_order_btn:visible, #formData_btn:visible, #voucher_general_btn:visible').first();
      if ($sub.length && $sub[0] !== $current[0]) {
        focusElement($sub[0]);
      }
    }
  }

  // ----------------------------------------------------
  // 1. Select2 Keyboard & Selection Handling
  // ----------------------------------------------------

  // When an option is selected in Select2, immediately advance to the next field (single select)
  $(document).on('select2:select', 'select', function(e) {
    var selectEl = this;
    if (!selectEl.multiple) {
      setTimeout(function() {
        moveFocus(selectEl, 1);
      }, 60);
    }
  });

  // Intercept Enter key on .select2-selection using CAPTURE phase so Select2 doesn't trap focus
  document.addEventListener('keydown', function(e) {
    if ((e.key === 'Enter' || e.keyCode === 13) && !e.altKey && !e.ctrlKey) {
      var target = e.target;
      var $selection = $(target).closest('.select2-selection');
      if ($selection.length) {
        var $container = $selection.closest('.select2-container');

        // If dropdown is open, let user choose from the search list
        if ($container.hasClass('select2-container--open')) {
          return;
        }

        // Dropdown is CLOSED:
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        var $select = $container.prev('select');
        if (!$select.length) {
          $select = $(target).closest('form, body').find('select[data-select2-id="' + $container.attr('id') + '"]');
        }

        if (e.shiftKey) {
          moveFocus($select[0] || target, -1);
          return;
        }

        // If select already has a chosen value (e.g. product already selected), move next!
        var curVal = $select.val();
        if (curVal && curVal !== '') {
          moveFocus($select[0] || target, 1);
          return;
        }

        // If empty ("Select Product"), open Select2 dropdown
        if ($select.length && typeof $select.select2 === 'function') {
          $select.select2('open');
        } else {
          moveFocus($select[0] || target, 1);
        }
      }
    }
  }, true); // Capture phase!

  // ----------------------------------------------------
  // 2. Native <select> Handling
  // ----------------------------------------------------
  $(document).on('keydown', 'select', function(e) {
    if ((e.key === 'Enter' || e.keyCode === 13) && !e.altKey && !e.ctrlKey) {
      // If initialized with select2, ignore here (handled on .select2-selection)
      if ($(this).hasClass('select2-hidden-accessible') || $(this).data('select2')) {
        return;
      }

      e.preventDefault();
      var $sel = $(this);

      if (e.shiftKey) {
        $sel.removeData('select-interacted');
        moveFocus(this, -1);
        return;
      }

      // If already opened/selected or already has value, move next
      if ($sel.data('select-interacted') || ($sel.val() && $sel.val() !== '')) {
        $sel.removeData('select-interacted');
        moveFocus(this, 1);
        return;
      }

      // Otherwise open native dropdown picker
      if (typeof this.showPicker === 'function') {
        try {
          $sel.data('select-interacted', true);
          this.showPicker();
        } catch (err) {
          $sel.removeData('select-interacted');
          moveFocus(this, 1);
        }
      } else {
        moveFocus(this, 1);
      }
    }
  });

  $(document).on('change', 'select', function() {
    $(this).data('select-interacted', true);
  });

  $(document).on('blur', 'select', function() {
    var self = this;
    setTimeout(function() {
      $(self).removeData('select-interacted');
    }, 300);
  });

  // ----------------------------------------------------
  // 3. Inputs & Textareas Handling (including Date pickers)
  // ----------------------------------------------------
  $(document).on('keydown', 'input, textarea', function(e) {
    if ((e.key === 'Enter' || e.keyCode === 13) && !e.altKey && !e.ctrlKey) {
      // Ignore if inside SweetAlert
      if ($(this).closest('.swal2-container').length) return;
      // Ignore if inside DataTables search box
      if ($(this).closest('.dataTables_filter').length) return;
      // Ignore if Select2 search input
      if ($(this).hasClass('select2-search__field')) return;

      // Textarea: Shift+Enter allows newline; plain Enter moves forward
      if (this.tagName.toLowerCase() === 'textarea') {
        if (e.shiftKey) return;
        e.preventDefault();
        moveFocus(this, 1);
        return;
      }

      e.preventDefault();

      if (e.shiftKey) {
        $(this).removeData('date-interacted');
        moveFocus(this, -1);
        return;
      }

      var inputType = (this.type || '').toLowerCase();

      // Date / Time inputs: 1st Enter opens calendar/time picker; next Enter moves forward
      if (inputType === 'date' || inputType === 'time' || inputType === 'datetime-local') {
        var $dateInput = $(this);
        if ($dateInput.data('date-interacted')) {
          $dateInput.removeData('date-interacted');
          moveFocus(this, 1);
          return;
        }

        // Open native calendar / date picker
        if (typeof this.showPicker === 'function') {
          try {
            $dateInput.data('date-interacted', true);
            this.showPicker();
            return;
          } catch (err) {
            $dateInput.removeData('date-interacted');
            moveFocus(this, 1);
            return;
          }
        } else {
          moveFocus(this, 1);
          return;
        }
      }

      // Special helper: if product code is entered, flag product name as interacted so enter advances
      if ($(this).is('#get_product_code') && $(this).val().trim() !== '') {
        $('#get_product_name').data('select2-interacted', true);
        $('#get_product_name').next('.select2-container').find('.select2-selection').data('select2-interacted', true);
      }

      moveFocus(this, 1);
    }
  });

  // Track interactions on date and time inputs
  $(document).on('change input', 'input[type="date"], input[type="time"], input[type="datetime-local"]', function() {
    $(this).data('date-interacted', true);
  });

  $(document).on('blur', 'input[type="date"], input[type="time"], input[type="datetime-local"]', function() {
    var self = this;
    setTimeout(function() {
      $(self).removeData('date-interacted');
    }, 300);
  });

  // ----------------------------------------------------
  // 4. Buttons Handling
  // ----------------------------------------------------
  $(document).on('keydown', 'button', function(e) {
    if ((e.key === 'Enter' || e.keyCode === 13) && !e.altKey && !e.ctrlKey) {
      if (e.shiftKey) {
        e.preventDefault();
        moveFocus(this, -1);
        return;
      }
      if ($(this).is('#addProductPurchase')) {
        e.preventDefault();
        $(this).trigger('click');
        return;
      }
    }
  });

  // ----------------------------------------------------
  // 5. Reset Focus After Row Add
  // ----------------------------------------------------
  $(document).on('click', '#addProductPurchase', function() {
    setTimeout(function() {
      // Reset Select2 display if present
      var $pName = $('#get_product_name');
      if ($pName.length && ($pName.hasClass('select2-hidden-accessible') || $pName.data('select2'))) {
        $pName.val('').trigger('change.select2');
      }
      // Refocus product entry
      if ($('#get_product_code').is(':visible') && !$('#get_product_code').prop('readonly') && !$('#get_product_code').prop('disabled')) {
        $('#get_product_code').focus().select();
      } else if ($pName.length) {
        var $pS2 = $pName.next('.select2-container').find('.select2-selection');
        if ($pS2.length) {
          $pS2.focus();
        } else {
          $pName.focus();
        }
      }
    }, 120);
  });

  // Export globally
  window.moveFocusByEnter = moveFocus;

})(jQuery);
