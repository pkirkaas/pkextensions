/**Copyight (C) 2016 by Paul Kirkaas - All Rights Reserved */
/* * JS Specific to Laravel */
$(function () {
  /*
  $("input.datepicker").datepicker({
    dateFormat: 'yy-mm-dd'
  });
  */
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
console.log("At least loaded...");
  // Attach to delete buttons & delete with ajax
  $('body').on('click','.ajax-delete', function (event) {
    console.log("We clicked!");
     return ajaxDeleteModel(
           $(this).attr('data-model'),
          $(this).attr('data-id'),
          $(this).attr('data-url'),
          $(this).attr('data-cascade'));
  });

});

  //To delete any PkModel instance with a single AJAX call.
  function ajaxDeleteModel(model, id, url, cascade) {
    url = url || '/ajax/delete';
    var res = $.ajax({
      url: url,
      data: {model, id, cascade},
      method: 'POST'
    }).done(function (data) {
      console.log("Returned from General Ajax w. data:", data);
      if (data.refresh === true) {
        window.location.reload(true);
      }
    });
  }


