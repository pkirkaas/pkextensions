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
/*
 $(".pk-dd-upload").ready( function() { 
 var hiddenfileinput = $('<input type="file">');
 });
 */

$(function () {
/*
  $(".pk-dd-upload").on('click', function (e) {
    var me = e.target;
    var hiddenfileinput = $('<input type="file" multiple>');
    hiddenfileinput.on('change', function (ec) {
      console.log("The type of files: "+typeof this.files);
      if (!this.files instanceof FileList) {
        return;
      }
      //var ifiles = this.files;

      //console.log("The selected items:",this.files,'ifiles',ifiles);
       //for (var i = 0, len = this.files.length; i < len; i++) {
      // console.log("File " + i, this.files[i], this.files.item(i));
      // }
      //$(me).trigger('drop', this.files);
      var onclick = 'onClk';
      $(me).trigger('drop', [this.files, onclick]);
    });
    hiddenfileinput.trigger('click');
  });

  $(".pk-dd-upload").on('dragenter', function (e) {
    e.preventDefault();
  });

  $(".pk-dd-upload").on('dragover', function (e) {
    e.preventDefault();
  });

  $(".pk-dd-upload").on('drop', function (e, files, onclick) {
    console.log("Entering drop; Files: onclick", onclick, files);
    if (onclick) {
      if (!files instanceof FileList) {
        return;
      }
    } else {
      files = e.originalEvent.dataTransfer.files;
    }

    var type = typeof files;
    console.log("FILES: " + type, files);
    var me = this;
    var url = $(this).attr('data-url') || '/ajax/upload';
    var finpname = $(this).attr('data-name') || 'upload';
    var params = parseStr($(this).attr('data-params'));

    e.preventDefault();
    //var files = e.originalEvent.dataTransfer.files;
    var formData = new FormData();
    formData.append(finpname, files[0]);

    appendObjectToFormdata(formData, params);

    $(this).addClass('ajax-loader');
    $.ajax({
      url: url,
      type: "POST",
      data: formData,
      contentType: false,
      cache: false,
      processData: false,
      success: function (data) {
        $(me).removeClass('ajax-loader');
        VEventDispatcher.trigger('fileUploaded', "A new File");
        //$(me).html(data);
      }
    }).done(function (data) {
      $(me).removeClass('ajax-loader');
    }).always(function (data) {
      $(me).removeClass('ajax-loader');
    });
  });
*/

  jQuery.fn.ddupload = function(options) {
    $(this).on('click', function (e) {
      var me = e.target;
      var hiddenfileinput = $('<input type="file" multiple>');
      hiddenfileinput.on('change', function (ec) {
        console.log("The type of files: "+typeof this.files);
        if (!this.files instanceof FileList) {
          return;
        }
        //var ifiles = this.files;

        //console.log("The selected items:",this.files,'ifiles',ifiles);
        /*
        for (var i = 0, len = this.files.length; i < len; i++) {
        console.log("File " + i, this.files[i], this.files.item(i));
        }
        */
        //$(me).trigger('drop', this.files);
        var onclick = 'onClk';
        $(me).trigger('drop', [this.files, onclick]);
      });
      hiddenfileinput.trigger('click');
    });

    $(this).on('dragenter', function (e) {
      e.preventDefault();
    });

    $(this).on('dragover', function (e) {
      e.preventDefault();
    });

    $(this).on('drop', function (e, files, onclick) {
      console.log("Entering drop; Files: onclick", onclick, files);
      if (onclick) {
        if (!files instanceof FileList) {
          return;
        }
      } else {
        files = e.originalEvent.dataTransfer.files;
      }

      var type = typeof files;
      console.log("FILES: " + type, files);
      var me = this;
      var url = $(this).attr('data-url') || '/ajax/upload';
      var finpname = $(this).attr('data-name') || 'upload';
      var params = parseStr($(this).attr('data-params'));

      e.preventDefault();
      //var files = e.originalEvent.dataTransfer.files;
      var formData = new FormData();
      formData.append(finpname, files[0]);

      appendObjectToFormdata(formData, params);

      $(this).addClass('ajax-loader');
      $.ajax({
        url: url,
        type: "POST",
        data: formData,
        contentType: false,
        cache: false,
        processData: false,
        success: function (data) {
          $(me).removeClass('ajax-loader');
          VEventDispatcher.trigger('fileUploaded', data.success);
          //$(me).html(data);
        }
      }).done(function (data) {
        $(me).removeClass('ajax-loader');
      }).always(function (data) {
        $(me).removeClass('ajax-loader');
      });
    });
  };



});

