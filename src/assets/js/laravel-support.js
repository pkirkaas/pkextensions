/* * JS Specific to Laravel */
$(function () {
  /*
  $("input.datepicker").datepicker({
    dateFormat: 'yy-mm-dd'
  });
  */
  $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
  });
});


