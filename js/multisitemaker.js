/**
 * @file
 * multisitemaker.js
 */

jQuery(function() {
  /**
   * Focus on the subdomain field on load.
   */
  jQuery('form#new_multisite_form input#subdomain_name').focus();

  /**
   * Limit the subdomain name to alphanumerics and hyphens.
   */
  var inputField = jQuery('form#new_multisite_form input#subdomain_name');
  inputField.keypress(function (e) {
    // Detect enter key events.
    if (e.charCode == 13) {
      return true;
    }

    //var regex = /^[A-Za-z0-9-]+$/;
    var regex = /^[A-Za-z0-9][A-Za-z0-9\-]*$/;
    //var regex = new RegExp("/^[A-Za-z][A-Za-z0-9]*$/");
    //var regex = new RegExp("/^[A-Za-z][A-Za-z0-9\-]*$/");
    //var regex = new RegExp("/^[A-Za-z][a-z0-9\-]+$/i");
    var fieldValue = $(this).val();
    var str = String.fromCharCode(!e.charCode ? e.which : e.charCode);

    console.log(e.charCode);
    console.log($(this));
    console.log(str);
    console.log(fieldValue);
    console.log(regex.test(fieldValue + str));

    if (regex.test(fieldValue + str)) {
      return true;
    }

    e.preventDefault();
    return false;
  });

  /**
   * Convert to lowercase.
   */
  inputField.keyup(function(){
    $(this).val($(this).val().toLowerCase());
  });
});
