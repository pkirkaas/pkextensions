/* 
 * This library manages if you have encoded model/instance details encoded as 
 * data-attributes in JS and you want to pop them into a dialog when the user
 * hovers or clicks on an element. Assumes pklib.js
 */


/** This section is for managing object details encoded into data attribute
 * values in the HTML generated by PHP.
 * First, we declare a global JS object "var decodedObjectCache = {};"
 * to hold/cache the decoded values so we don't have to decode them again every time.
 * 
 * Function "getDecodedData(objtype,objid)" returns the data object details for
 * object of type=objtype and id=objid.
 * 
 * First checks the global cache "decodedObjectCache.type.id" - if it exists, 
 * returns it. If not, checks the page for element of class: 'encoded-data-holder',
 * with the elements "data-encoded-data-objtype=type" and 'data-encoded-data-objid=id'
 * 
 *  If it finds that element, parses the value of 'data-encoded-data-data' into
 *  a JavaScript object, assigns the result to "decodedObjectCache.objtype.objid", and
 *  returns it.
 * 
 * @returns object - the decoded data object.
 */

//var decodedObjectCache = {};
var decodedObjectCache = null;

/** We get the decoded data for a particular object instance (objtype/objid)
 * getDecodedData(objtype,objid): returns the data for that object
 * OR for all instances of that type:
 * getDecodedData(objtype): data for all instances of that type, keyed by ID
 * OR all the encoded data we have on the page:
 * getDecodedData(): ALL the decoded data, keyed by type/id
 * 
 * @param string objtype
 * @param string objid
 * @returns object data
 */


/* As defined in app.blade.php
   var popDefObj = {
        dataModelType:'data-model-type-name',
        dataModelId:'data-model-id',
        jsPopupTmpCallerDataAttr:'data-js-popup-template-calls',
        jsPopupTmpCalledDataAttr:'data-js-popup-template-called',
        encodedDataHolderClass:'encoded-data-holder',
        encDatMdlDataAttr:'data-encoded-data-objtype',
        encDatIdDataAttr:'data-encoded-data-objid',
        encDatDatDataAttr:'data-encoded-data-data',
        popTemplateClass:'pop-hidden-js-template',
        popCallerClass:'pop-details-for-obj',
        popAttrNameDataAttr:'data-enc-attr-name',
        valueTemplateClass:'enc-attr-val-tpl',
        valueHolderClass:'enc-attr-val-holder-tpl'
     };
*/



/**
 * Gets decoded object attribute data for the given model/id - or for all
 * instances of model if id not given, or for all known if neither given
 * @param string|object|nul objtype - if string, the object type. If object,
 *   should be of the form: {objtype:objtype, objid:objid}
 *   if null, all encoded data returned.
 * @param string|null objid
 * @returns {decodedObjectCache}
 */
function getDecodedData(objtype,objid) {
  if (decodedObjectCache === null) {
    inititializeDecodedObjectCache();
  }
  if (isObject(objtype)) {
    objid = objtype.objid;
    objtype = objtype.objtype;
  }
  
  if (objtype === undefined) {
    return decodedObjectCache;
  }
  if (decodedObjectCache[objtype] === undefined) {
    decodedObjectCache[objtype] = {};
    console.log("The cache attribute ["+objtype+"] was undefined");
  }
  if (objid === undefined) {
    return decodedObjectCache[objtype];
  }
  if (decodedObjectCache[objtype][objid] === undefined) {
    decodedObjectCache[objtype][objid] = {};
  }
  return decodedObjectCache[objtype][objid];
}

function inititializeDecodedObjectCache() {
  if (decodedObjectCache === null) {
    decodedObjectCache = {};
  }
  var encodedEls = $('.'+popDefObj.encodedDataHolderClass);
  if (!encodedEls.length) {
    return decodedObjectCache;
  }
  encodedEls.each(function() {
    var objtype = $(this).attr(popDefObj.encDatMdlDataAttr);
    var objid = $(this).attr(popDefObj.encDatIdDataAttr);
    var encoded = $(this).attr(popDefObj.encDatDatDataAttr);
    var decoded =  $.parseJSON(encoded);
    if  (decodedObjectCache[objtype] === undefined) {
      decodedObjectCache[objtype] = {};
    }
    decodedObjectCache[objtype][objid] = decoded;
  });
  return decodedObjectCache;
}


function objDataDecode(objtype, objid) {
  var datael = $('.encoded-data-holder[data-encoded-data-objtype="'+
          objtype+'"][data-encoded-data-objid="'+
          objid + '"]');
  var encoded = datael.attr('data-encoded-data-data');
  console.log ('encoded:', encoded);
  var decoded =  $.parseJSON(encoded);
  console.log ('decoded:', decoded);
  return decoded;
}



$(function () {
  console.log("Trying to get data atts");
  //var res = getDecodedData('borrower','1');
  var res = getDecodedData();
  console.log("RES:", res);
  /*
  var data_atts = $('div.data-atts-holder');
  if (data_atts.length) {
    var enc_data = data_atts.attr('data-atts');
    console.log("The encoded data", enc_data);
    var dec_obj = $.parseJSON(enc_data);
    console.log("The Decoded Obj:", dec_obj);
  }
  */

});





/** Special pop-up JS to show model/id details when hovering...
 * 
 * @param {type} theVar
 * @param {type} subStr
 * @returns {Boolean}
 */
//$('body').on('hover', '.'+popDefObj.popTemplateClass, function (event) {
//$('body').on('hover', '['+popDefObj.jsPopupTmpCallerDataAttr+']', function (event) {
//$('body').on('hover', '.'+popDefObj.popCallerClass, function (event) {
$('body').on('click', '.'+popDefObj.popCallerClass, function (event) {
  console.log("Hovering...");

  var src = $(event.target).attr(popDefObj.jsPopupTmpCallerDataAttr);
  if (!src) return;
  var dlg = $('.'+popDefObj.popTemplateClass+'['+popDefObj.jsPopupTmpCalledDataAttr+'="' + src + '"]');
  if (dlg.length === 0) return;
  var dlgHtml = dlg.prop('outerHTML');
  console.log("DLGHTML: "+dlgHtml);
  //Find the nearest parent with model & id set
  var inst = $(this).closest('['+popDefObj.dataModelType+']');
  var model = inst.attr(popDefObj.dataModelType);
  var id = inst.attr(popDefObj.dataModelId);
  if (!id || !model) {
    console.log("Failed to get one of ["+model+']['+id+']');
    return false;
  }
  var encdata=getDecodedData(model,id);
  if (!encdata || !isObject(encdata)) {
    console.log("Failed to get encdata for ["+model+']['+id+']: Got:', encdata);
    return false;
  }
  console.log("Got ENCDATA!", encdata);
  dlg = buildDetailDialog(dlgHtml, encdata);
  var dlg_title = dlg.attr('data-title');
  if ((dlg_title === undefined) || !dlg_title) dlg_title='Details';
  console.log("Got dlg!", dlg.html());
  var dialogOpts = {
    modal: true,
    autoOpen: true,
    width: 600,
    title: dlg_title,
    buttons: [{
        text: 'Close',
        click: function () {
          $(this).dialog('destroy');
        }
      }
    ]
  };
  //var dialogOptions = dialogDefaults;
  //dlg.modal('show');
  dlg.dialog(dialogOpts);

});

/** Takes an encoding template and recursively iterates through it building
 * the custom data dialog
 * @param HTML String/jQuery domBit - the template or part of it
 * @param plain object encdata - the data for the model instance
 * @param boolean hideempty - default: true ('false' option not implemented yet)
 *   - if true, don't show elements for which there is no data
 * @returns HTML string/DOM for the dialog from the template, with values inserted
 */
function buildDetailDialog(template,encdata,hideempty) {
  hideempty = (hideempty === undefined)?true:hideempty;
  if (isEmpty(encdata) && hideempty) return false;
  template=jQuerify(template).clone();
  if (!template.length) return false;
  var dataFields = template.find('['+popDefObj.popAttrNameDataAttr+']');
  dataFields.each(function() {
    console.log("In each data-el - this: ", this);
    var jqthis = $(this);
    var encAttName = jqthis.attr(popDefObj.popAttrNameDataAttr);
    if ((encAttName === undefined) || !encAttName) { //Bad encAttName - delete
      console.log("The template had a bad data-attribute name");
      template.closest('.'+popDefObj.valueTemplateClass).remove();
    }
    if (encdata[encAttName] === undefined) {
      if (hideempty) template.closest('.'+popDefObj.valueTemplateClass).remove();
    } else { //We have a value - insert it!
      jqthis.htmlFormatted(encdata[encAttName]);
    }
  });
  return template;
}

/** Takes an encoding template and recursively iterates through it building
 * the custom data dialog
 * @param HTML String/jQuery domBit - the template or part of it
 * @param plain object encdata - the data for the model instance
 * @param boolean hideempty - default: true ('false' option not implemented yet)
 *   - if true, don't show elements for which there is no data
 * @returns HTML string/DOM for the dialog from the template, with values inserted
 */
/*
function buildDetailsDialog(domBit, encdata, hideempty) {
  domBit = jQuerify(domBit).clone();
  if (domBit.hasClass(popDefObj.valueTemplateClass)) {
    //... it is a template row for SOME data element. Get the enc data key 
    var enc_att_name = domBit.attr(popDefObj.popAttrNameDataAttr);
    if ((enc_att_name === undefined) || !enc_att_name ||
            (encdata[enc_att_name] === undefined)) {
      //We don't have a value for this row; leave blank
      return false;
    }
    var encval = encdata[enc_att_name]; //We have a value - insert it 

  }

}

function descendAndSet(template, encdata) {
  template = jQuerify(template).clone();
  if (template.hasClass(popDefObj.valueTemplateClass)) {

  }
  var innershell = $(template[0].cloneNode());
  var innercontent = template.contents();
  innercontent.each(function() {
    innershell.append(descendAndSet(this));

  });
  
}
*/

