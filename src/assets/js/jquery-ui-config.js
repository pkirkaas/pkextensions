/* 
 * Generic jQuery UI Initialization - uses standardized class names to attach
 * datepicker, etc.
 */


$(function () {
  console.log("In jqui config init");
  $('input.datepicker.auto-attach').datepicker( { 
    dateFormat: 'yy-mm-dd'
  });
});



