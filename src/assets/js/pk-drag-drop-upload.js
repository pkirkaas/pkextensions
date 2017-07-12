/** 
 * A jQuery/pklib based Drag and Drop upload -
 * Different because you can have as many as you like on a page, different 
 * types, different URLs, Parameters, etc, & you don't have to write another
 * line of JS - just configure each w. data-XXX attributes
 * Just make the D&D element class: '.pk-dd-upload'
 * data-postparams: A URL encoded string of parameters to include w. the post
 * data-url: The url to post to, or '/ajax/upload' by default
 * data-name: The file-input name to use, or 'upload' by default
 */

$(function () {
 $(".pk-dd-upload").on('dragenter', function (e){
  e.preventDefault();
  $(this).css('background', '#BBD5B8');
 });

 $(".pk-dd-upload").on('dragover', function (e){
  e.preventDefault();
 });

 $(".pk-dd-upload").on('drop', function (e){
   var me = this;
   var url = $(this).attr('data-url') || '/ajax/upload';
   var finpname = $(this).attr('data-name') || 'upload';
   var params = parseStr($(this).attr('data-params'));

  $(this).css('background', '#D8F9D3');
  e.preventDefault();
  var files = e.originalEvent.dataTransfer.files;
  var formData = new FormData();
  formData.append(finpname, files[0]);

  appendObjectToFormdata(formData,params);

  $.ajax({
    url: url,
    type: "POST",
    data: formData,
    contentType:false,
    cache: false,
    processData: false,
    success: function(data){
      VEventDispatcher.trigger('fileUploaded',"A new File");
      $(me).html(data);
    }
  });
 });




});

