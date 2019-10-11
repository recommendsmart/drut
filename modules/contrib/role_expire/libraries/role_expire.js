/**
 * @file
 * Role Expire js
 *
 * Set of jQuery related routines.
 */


(function ($) {

  Drupal.behaviors.role_expire = {
    attach: function (context, settings) {

      $('input.role-expire-role-expiry', context).parent().hide();

      // No key change needed if Role Assign module is used.
      rolesKey = 'roles';
      if ($('#edit-role-change').length > 0) {
        // Role Delegation module is used.
        var rolesKey = 'role-change';
      }

      $('#edit-' + rolesKey + ' input.form-checkbox', context).each(function() {
        var textfieldId = this.id.replace(rolesKey, "role-expire");

        // Move all expiry date fields under corresponding checkboxes
        $(this).parent().after($('#'+textfieldId).parent());

        // Show all expiry date fields that have checkboxes checked
        if ($(this).attr("checked")) {
          $('#'+textfieldId).parent().show();
        }
      });

      $('#edit-' + rolesKey + ' input.form-checkbox', context).click(function() {

        var textfieldId = this.id.replace(rolesKey, "role-expire");

        $('#'+textfieldId).parent().toggle();
      });
    }
  }

})(jQuery);
