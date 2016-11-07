/* 
 * Custom js for sbc
*/
//alert("Loaded custom js");

/** If a form is modified, makes user confirm to leave, unless action is
 * to submit
 * @returns {undefined}
 */
$(function () {
  $('.pk-table-column, .dd-col, .pk-eq-col').matchHeight();
});
$(function () {
  $('body').on('change', 'form.chck-frm', function (event) {
    $(window).on('beforeunload', function (event) { 
      var confirmationMessage = "Unsaved Changes";
      event.preventDefault();
      event.returnValue = confirmationMessage; 
      return confirmationMessage;
    });
  });
  $('form.chck-frm').submit (function (event) {
    console.log("Got in onload - trying 'off' ...");
    $(window).off('beforeunload');
  });
});


/** Toggle Borrower Profile 'planning' & dates */
$(function () {
  $('body').on('change', 'input.toggle-planning', function (event) {
    if ($(event.target).prop('checked')) {
      $('input.toggle-dates').prop('value',null);
      $('input.toggle-dates').prop('disabled',true);
    } else {
      $('input.toggle-dates').prop('disabled',false);
    }
  });
});