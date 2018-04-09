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
//Can work this to drag & drop image URLs, like with a post:
/** 4 data- att options - data-imgclass for the CSS class of the image,
 *  data-ajaxurl if we want to make an AJAX call, & data-params, URL encoded parameter
 *  data-name - optional the name of the form-control, or defaults to url
 */
$(function () {
  $('.drop-url').on('drop',function(e) {
    e.preventDefault();
    //console.log("Dropped here, dataTransfer: ",e.originalEvent.dataTransfer.getData('text/html'));
    console.log("Dropped here, dataTransfer: ",e.originalEvent.dataTransfer.getData());
    var url = $(e.originalEvent.dataTransfer.getData('text/html')).filter('img').attr('src');
    if (!url) {
      return;
    }
    var imgclass = $(this).attr('data-imgclass') || 'avatar';
    var params  = $(this).attr('data-params');
    var ajaxurl  = $(this).attr('data-ajaxurl');
    var name = $(this).attr('data-name') || 'url';
    if (!params) {
      params = {};
    } else if (typeof params === 'string') {
      params = parseStr(params);
    }
    params[name]=url;
    console.log('Params:',params);
      
    jQuery('<img/>', {
          src: url,
          alt: "resim",
          class: imgclass
    }).appendTo(this);            
    if (ajaxurl) {
      $.ajax({
        url: ajaxurl,
        data: params,
        type: 'POST',
        success: function(data) {
          console.log("Succeeded w. data:",data);
        }
      });
    }
  });
});

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
      console.log("Trying to upload file w. ajax url:",url, "Wnd form data:",formData);
      

      axios.post(url,formData)
        .then(function(response) { console.log("success wth response: ",response);})
        .catch(function(response) { console.log("BIG FAILURE wth response: ",response);})
;



      /** Try with Axios...
      $.ajax({
        url: url,
        type: "POST",
        data: formData,
        contentType: false,
        cache: false,
        processData: false,
        success: function (data) {
          console.log ("Seems like we succeeded upload w. data:",data);
          $(me).removeClass('ajax-loader');
          VEventDispatcher.trigger('fileUploaded', data.success);
          //$(me).html(data);
        }
      }).done(function (data, d2, d3) {
        var j=data;
        console.log("Succedded to drag & drop, data:",
          data, 'd2', d2, 'd3', d3,
          {response:j.response,  responseType:j.responsType});
        $(me).removeClass('ajax-loader');
        VEventDispatcher.trigger('fileUploaded', 'avatar');
      }).fail(function (data, d2, d3) {
        var j = data;
        console.log("Failed to drag & drop,respdata:",
          data, 'd2', d2, 'd3', d3,
          {response:j.response,  responseType:j.responseType, url:url, formData:formData});
        $(me).removeClass('ajax-loader');
        VEventDispatcher.trigger('fileUploaded', 'avatar');
      });
    });
    console.log("Exiting ddupload");
  };*/

  });
  };
  
});

