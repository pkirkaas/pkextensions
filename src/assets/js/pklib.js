/** Library of convenient JS/jQuery functions. Not PKMVCFramework Specific
 * @author    Paul Kirkaas
 * @email     p.kirkaas@gmail.com
 * @copyright Copyright (c) 2012-2014 Paul Kirkaas. All rights Reserved
 * @license   http://opensource.org/licenses/BSD-3-Clause  
 */

$(function () {

  /** Two general kinds of editing/creating/deleting multiple similar items on 
   * the same page - where you might want to make multiple changes to multiple items
   * and save all changes at once - so single Form/ single "Submit" button on page.
   * OR 
   * only create/edit/delete one item at a time - so each item is it's own form,
   * with own submit and delete buttons.
   */

  /*** Save changes to multple items with single "Submit" ***/
  /** Universal delete - works everywhere if you give the button at least
   * the classes "js btn data-set-delete", and wrap every deletable dataset
   * with '<div class="deletable-data-set"> xxxxxx <div class='js btn data-set-delete'>Delete</div></div>
   */

  /**Creating from multiple subforms from Templates --- Wrap the form AND template in.
   * This example for Single form, changing multiple items per submit. 
   <form method='post'>
   <div class="templatable-data-sets">
   <div class='deletable-data-set'>
   <input type='hidden' name='tablename[0][id]' value='xxx1' />
   <input type='text' name='tablename[0][fieldname]' value='yyy1' />
   <span class='js btn data-set-delete'>Delete</span>
   </div> <!-- Close deletable-data-set -->
   <div class='deletable-data-set'>
   <input type='hidden' name='tablename[1][id]' value='xxx2' />
   <input type='text' name='tablename[1][fieldname]' value='yyy2' />
   <span class='js btn data-set-delete'>Delete</span>
   </div> <!-- Close deletable-data-set -->
   <span class='js btn create-new-data-set' data-itemcount='2'>New?</span>
   <input type='submit' value='Save' />
   <fieldset disabled class='template-container' style='display:none;'> 
   <div class='deletable-data-set'>
   <input type='hidden' name='tablename[__CNT_TPL__][id]' />
   <input type='text' name='tablename[__CNT_TPL__][fieldname]'/>
   <span class='js btn data-set-delete'>Delete</span>
   </div> <!-- Close deletable-data-set -->
   </fieldset> <!-- close template container -->
   </div> <!-- close templatable-data-sets -->
   </form>
   
   * 
   * Then, these two assignments work EVERYWHERE ON THE PAGE
   */



  $('body').on('click', '.js.btn.data-set-delete', function (event) {
    $(event.target).closest('div.deletable-data-set').remove();
  });

  /** This serves both creating multiple new instances before submitting when
   * itemcount is implemented, AND, just creating a single new instance from the
   * template and hiding the NEW/CREATE button when it is created.
   * <em>The create new dataset button should have <tt>data-itemcount</tt></em> set
   * either to an Int of the current item count (if we will create many new items
   * before submitting) OR ELSE to <tt>single</tt>, in which case it will just 
   * create a new emtpy form from the template and hide itself 
   */
  $('body').on('click', '.js.btn.create-new-data-set', function (event) {
    //Get a copy of the template element
    var tpl = $(event.target).closest('div.templatable-data-sets').find('.template-container').first().html();

    //Get the current count element (one higher than the max index, if multi)
    var cnt = $(event.target).attr('data-itemcount');
    var newstr = tpl;
    if (cnt === 'single') {
      $(event.target).css('display', 'none');
    } else {
      cnt = parseInt(cnt);
      newstr = tpl.replace(/__CNT_TPL__/g, cnt);
      $(event.target).attr('data-itemcount', ++cnt);
    }
    $(event.target).before(newstr);
  });
  $('body').on('click', '.pkmvc-button.showHelp', function (event) {
    showHelpDialog();
  });
});

$(function () {
  $('.js-fade-out').fadeOut(8400, function () {
    $(this).css('display', 'none');
  });
});

function showHelpDialog() {
  var helpDialog = showHelpDialog.helpDialog;
  if (!helpDialog) {
    helpDialog = makeDialog('', {minHeight: 500, minWidth: 1100});
    showHelpDialog.helpDialog = helpDialog;
  }
  if ($('#helpTitle').html()) {
    helpDialog.title($('#helpTitle').html());
  } else {
    helpDialog.title('Help for this Page');
  }
  helpDialog.html($('#helpContent').html());
  helpDialog.dialog('open');
}


/** If have a 'position: fixed' top menu row, (id='top-menu-fixed'), change the 
 * body padding-top to adjust to the height of the menu on window resize and init  */
function top_pad_body_to_fixed(menu) {
  if (!menu)
    menu = '#top-menu-fixed';
  if (!$(menu).length)
    return;
  var fixed_menu_height = $(menu).height();
  $('body').css('padding-top', fixed_menu_height + 'px');
}

/** If there is a 'position:fixed' top menu, top-pad body */
$(function () {
  top_pad_body_to_fixed();
});
$(window).resize(function () {
  top_pad_body_to_fixed();
});

/**
 * Takes an array of GET key/value pairs, adds (or replaces) them, and redirects
 * @param {type} getArr
 */
function addGetsAndGo(getArr) {
  var gets = getGets(); //Array of existing GET params
  var outGets = $.extend({}, gets, getArr);
  setGetsAndGo(outGets);
}

/** Takes an array of GET key/value pairs and sets them as the GET
 * parameters to the current path, totally removing any current GET
 * params not in this array.
 * @param {type} getArr
 */
function setGetsAndGo(getArr) {
  var queryStr = getArrToStr(getArr);
  var basePath = getBasePath();
  window.location = basePath + queryStr;
}

function getBasePath() {
  var basePath = window.location.protocol + '//' + window.location.hostname
          + window.location.pathname;
  return basePath;
}


/** Deletes the closest parent that matches the selector
 * To delete a row/subform in a multi-row form
 * @param node element - node - usually the button clicked on
 * @param string selector - jquery/css selector
 */
function deleteClosest(element, selector) {
  $(element).closest(selector).remove();

}


/**
 * Refresh current page with new GET parameter value
 * Adds the parameter if it doesn't exist, or replaces the current value
 *
 * @param parmName: the name of the GET parameter
 * @param parmValue: the value of the GET parameter
 */
function refreshNewGet(parmName, parmValue) {
  var gets = getGets();
  //Kludge -- if changing perpage, reset page to 1
  if (parmName == 'perpage') {
    gets['page'] = '1';
  }
  gets[parmName] = parmValue;
  //Rebuild GET query string
  var getstr = '';
  for (var parname in gets) {
    if (gets[parname]) {
      getstr = getstr + '&' + parname + '=' + encodeURIComponent(gets[parname]);
    }
  }
  if (getstr) {
    getstr = '?' + getstr.substr(1);
  }
  window.location = window.location.pathname + getstr;
}


/**
 * Returns associative array of named "GET" parameters and values
 */
function getGets() {
  var queryStr = window.location.search.substr(1);
  var params = {};
  if (queryStr == '')
    return params;
  var prmarr = queryStr.split("&");

  for (var i = 0; i < prmarr.length; i++) {
    var tmparr = prmarr[i].split("=");
    params[tmparr[0]] = tmparr[1];
  }
  return params;
}


/**
 *Converts an array of key/values to a an '&' separated GET string of params
 *values. 
 * @param {type} getArr: Array of GET key/value pairs
 * @returns String: converted array of GET params to a query string
 */
function getArrToStr(getArr) {
  var retstr = '?';
  for (var paramName in getArr) {
    if (getArr.hasOwnProperty(paramName)) { // paramName is not inherited
      retstr += (paramName + '=' + getArr[paramName] + '&');
    }
  }
  retstr = retstr.substring(0, retstr.length - 1);
  return retstr;
}

/** 
 * Takes an associative array of key/value pairs and returns a GET param str
 * TODO: URL encode? But what if existing param values are already URLencoded?
 * @param Array getArr: array of key/value pairs
 * @returns String query get parameter string
 */
function setGets(getArr) {
}



/** Rounds a number to two decimal places. 
 * TODO: Make 2 the default, with additional optional parameter
 * @param {type} numberToRound
 * @returns {Number}
 */
function roundTo2Decimals(numberToRound) {
  return Math.round(numberToRound * 100) / 100
}

/**
 * Returns a "cousin" of the given object, as the first matched descendent
 * of the first matched ancestor
 * @param String parentSelector: the jQuery string for the parent to look for
 * @param String cousinSelector: the jQuery string for the cousin to look for
 * @param {type} me: the JS element to find the cousin of (if empty, this)
 * @returns {jQuery} -- the cousin, as a jQuery object
 */
function getCousin(parentSelector, cousinSelector, me) {
  return  getCousins(parentSelector, cousinSelector, me, true);
}

/** See definition of getCousin, above. This returns all cousins, unless the
 * "first" parameter is true, in which case it returns only the one, first
 * @param {type} parentSelector
 * @param {type} cousinSelector
 * @param {type} me
 * @param int first: Retrun only the first cousin?
 * @returns {getCousins.cousins}
 */
function getCousins(parentSelector, cousinSelector, me, first) {
  if (me == undefined) {
    me = this;
  }
  me = jQuerify(me);
  var cousins = me.closest(parentSelector).find(cousinSelector);
  if (first) {
    cousins = cousins.first();
  }
  return cousins;
}

/**
 * Given a JS DOM object, or a string, or a jQuery object, returns the 
 * corresponding jquery object. Used to normalize an argument to a function 
 * that might be any way of specifying and object
 * @param jQuery|string|obj: arg
 * @returns jQuery
 */
function jQuerify(arg) {
  if (arg instanceof jQuery) {
    return arg;
  }
  return jQuery(arg);
}

/**
 * Adds the given class to the object, and removes all the other classes in
 * the array.
 * @param string classToAdd
 * @param array classesToRemove
 * @param jQuerifyable obj
 * @returns the object
 */
function addClassAndClear(classToAdd, classesToRemove, obj) {
  //var possibleStates = array['pass', 'fail', 'unknown'];
  //Check for state in array of possibleStates
  var idx = classesToRemove.indexOf(classToAdd);
  if (idx == -1) { //Something is wrong, bail
    return;
  }
  /*
   classesToRemove.splice(idx, 1);
   if (obj == undefined) {
   obj = this;
   }
   */
  obj = jQuerify(obj);
  for (var idx in classesToRemove) {
    obj.removeClass(classesToRemove[idx]);
  }
  obj.addClass(classToAdd);
}

/**
 * Takes a numeric value and makes it maximum N decimals (default 2),
 * BUT trims trailing 0's
 * TODO: This only works for 2 decimal places, for some reason. Other values of
 * N have no effect...
 * @param number val: The value to trim
 * @param integer N: The number of places
 * @returns number: The trimmed number
 */
function maxNdecimals(val, N) {
  N = N || 2;
  //return val.toFixed(2).replace(/0{0,2}$/, "");
  var regexstr = "0{0," + N + "}$";
  //console.log("RegExStr:", regexstr);
  var regex = new RegExp(regexstr);
  return val.toFixed(N).replace(regex, "");
}

/** Especially for file upload elements, which are read-only for security, 
 * and can only be reset. Temporarilly create another form around it, reset
 * that form, then unwrap it.
 * @param jQuerifyiable e
 */
function resetFormElement(e) {
  e = jQuerify(e);
  e.wrap('<form>').closest('form').get(0).reset();
  e.unwrap();
}

/**
 * Returns all the classes of the object
 * @param jQuerifyable el
 * @returns array of classes
 */
/* TODO: Debug at some point; getting "split of undefined" errors"
 function getClasses(el) {
 if (el == undefined) {
 el = this;
 }
 el = jQuerify(el);
 if (!(el instanceof jQuery) ) {
 return false;
 }
 var classes = el.attr('class').split(' ');
 return classes;
 }
 */

/** Pop up quick jQuery Dialog Boxes. See below for better
 *  - this one is even quicker and dirtier
 *  A dialog to be initially hidden then displayed at the click of a button
 *  should wrapped in a div with class 'js-dialog'. The div should also have
 *  a data-dialog attribute with some unique, arbitrary content, like what it's for.
 *  
 *  This function will automatically attach to any elements that have the
 *  class js-dialog-button. These elements should also have the same data-dialog
 *  value set as the dialog it will launch.
 *  The dialog container can also have a lot of data-xxx options to initialize
 *  the dialog
 * @param data-title - the title on the dialog box
 * @param - you can set most of the options for the dialog box in text-optName attributes
 * @returns {makeDialog.dlg|$}
 */

$('body').on('click', 'img.avatar', function (event) {
  console.log("Clicking..");
});
$('body').on('hover', 'img.avatar', function (event) {
  console.log("Hovering..");
});

$(function () {
  var dlgonload = $('.dialog-on-load');
  console.log("Loaded...");
  if (dlgonload && dlgonload.hasClass('js-dialog-content')) {
    console.log("We found a dialog on load!");

    //Let's make a dialog box right away!
    /*
     var param1 = dlgonload.attr('data-param1');
     var param2 = dlgonload.attr('data-param2');
     var param3 = dlgonload.attr('data-param3');
     var src = dlgonload.attr('data-dialog');
     var clone = dlgonload.attr('data-clone');
     */
    //var dlgHtml = dlgonload.prop('outerHTML');
    var closeText = dlgonload.attr('data-closetext');
    if (!closeText)
      closeText = 'Okay';
    var dialogDefaults = {
      modal: true,
      autoOpen: true,
      width: 600,
      resizable: true,
      draggable: true,
      buttons: [{
          text: closeText,
          click: function () {
            $(this).dialog('close');
          }

          //Cancel : function () { $(this).dialog('destroy'); }
          /*
           Okay: function () {
           $(this).dialog('close');
           }
           */
        }]
    };
    var dialogOptions = dialogDefaults;
    var overridableOptions = ['modal', 'autoOpen', 'buttons', 'closeOnEscape',
      'dialogClass', 'title', 'minHeight', 'minWidth', 'width', 'height'];
    var optName = optVal = null;
    for (var key in overridableOptions) {
      optName = overridableOptions[key];
      optVal = dlgonload.attr('data-' + optName);
      //console.log("KEY",key,'OptName',optName, 'optval', optVal);
      if (optVal)
        dialogOptions[optName] = optVal;
      optName = optVal = null;
    }
    dlgonload.dialog(dialogOptions);
    dlgonload.dialog('open');
    //return dlgonload;
  }
});


$('body').on('click', '.js-dialog-button', function (event) {
  var param1 = $(event.target).attr('data-param1');
  var param2 = $(event.target).attr('data-param2');
  var param3 = $(event.target).attr('data-param3');
  var src = $(event.target).attr('data-dialog');
  var clone = $(event.target).attr('data-clone');
  clone = true;
  var dlg = $('.js-dialog-content[data-dialog="' + src + '"]');
  if (dlg.length === 0)
    return;
  var dlgHtml = dlg.prop('outerHTML');
  //console.log('dlgHtml', dlgHtml, 'dlg', dlg);
  //if (clone) dlg = dlg.clone();
  /*
   dlg = dlg.replace(/__TPL_PARAM1__/g, param1);
   dlg = dlg.replace(/__TPL_PARAM2__/g, param2);
   dlg = dlg.replace(/__TPL_PARAM3__/g, param3);
   */
  dlgHtml = dlgHtml.replace(/__TPL_PARAM1__/g, param1);
  dlgHtml = dlgHtml.replace(/__TPL_PARAM2__/g, param2);
  dlgHtml = dlgHtml.replace(/__TPL_PARAM3__/g, param3);
  //console.log("After all the replacements, dlgHtml:",dlgHtml);
  //if (clone) console.log('clone was true', clone);
  dlg = $(dlgHtml);
  //else  console.log('clone was false', clone);
  //opts.title = dlg.attr('data-title');
  var closeText = $(event.target).attr('data-closetext');
  if (!closeText)
    closeText = dlg.attr('data-closetext');
  if (!closeText)
    closeText = 'Okay';
  var dialogDefaults = {
    modal: true,
    autoOpen: false,
    width: 600,
    buttons: [{
        text: closeText,
        click: function () {
          $(this).dialog('close');
        }
      }
    ]
  };
  var dialogOptions = dialogDefaults;
  //If the below options are data-XXX names in the dialog, use them...
  //But the button gets to override the dialog...
  var overridableOptions = ['modal', 'autoOpen', 'buttons', 'closeOnEscape',
    'dialogClass', 'title', 'minHeight', 'minWidth', 'width', 'height'];
  var optName = optVal = null;
  for (var key in overridableOptions) {
    optName = overridableOptions[key];

    optVal = $(event.target).attr('data-' + optName);
    if (!optVal)
      optVal = dlg.attr('data-' + optName);
    //console.log("KEY",key,'OptName',optName, 'optval', optVal);
    if (optVal)
      dialogOptions[optName] = optVal;
    optName = optVal = null;
  }

  //console.log("DialogOpts:", dialogOptions);
  dialogClass = dialogOptions['dialogClass'];
  console.log('dialogOptions', dialogOptions);
  dlg.dialog(dialogOptions);
  dlg.title = function (title) {
    dlg.dialog('option', 'title', title);
  };
  //dlg.dialog('option','dialogClass', dialogClass);
  dlg.dialog('open');
  return dlg;

});


/** Makes a jQuery DialogBox - as simply as possible by providing
 * defaults, and customizable by overriding defaults with options.
 * Can also take the basic return dialog box and customize after creation,
 * like dlg.html("Whatever you want") for the content.
 * @param {type} selector: The basis of the Dialog Box.
 * @param Object opts to override the defaults 
 *
 * @returns jQuery Dialog Box with extra methods:
 *   @method title(title) Change the title after the box is created
 */
function makeDialog(selector, opts) {
  opts = opts || {};
  selector = selector || '<div></div>';

  var dlg = $(selector);
  var dialogDefaults = {
    modal: true,
    autoOpen: false,
    width: 600,
    buttons: {
      //Cancel : function () { $(this).dialog('destroy'); }
      Cancel: function () {
        $(this).dialog('close');
      }
    }
  };
  var dialogOptions = dialogDefaults;
  //If the below options are property names in the opts arg, use them...
  var overridableOptions = ['modal', 'autoOpen', 'buttons', 'closeOnEscape',
    'dialogClass', 'title', 'minHeight', 'minWidth'];
  for (var key in overridableOptions) {
    var opt = overridableOptions[key];
    if (opts.hasOwnProperty(opt)) {
      dialogOptions[opt] = opts[opt];
    }
  }
  console.log("OverridableOpts", overridableOptions, 'opts', opts, 'DialogOpts', dialogOptions);
  if (opts && opts.title) {
    dlg.attr('title', opts.title);
  }
  if (opts && opts.html) {
    dlg.html(opts.html);
  }
  dlg.dialog(dialogOptions);
  dlg.title = function (title) {
    dlg.dialog('option', 'title', title);
  };
  return dlg;
}

/** Example Usage...
 
 function showHelpDialog() {
 var helpDialog = showHelpDialog.helpDialog;
 if (!helpDialog) {
 helpDialog = makeDialog('', {minHeight:500,minWidth:1100});
 showHelpDialog.helpDialog = helpDialog;
 }
 helpDialog.title('Help for this Admin Page');
 helpDialog.html($('#helpContent').html());
 helpDialog.dialog('open');
 };
 
 //HTML
 <div id='helpContent'>
 <div class='help-content'>
 <h1>Help Header</h1>
 <p>Do this to accomplish that.
 </div>
 </div>
 
 //CSS
 #helpContent {
 display: none;
 }
 
 */



function containsSubstr(theVar, subStr) {
  if (!theVar)
    return false;
  if (!subStr)
    return false;
  subStr = subStr.toString();
  if (!subStr)
    return false;
  theVar = theVar.toString();
  if (!theVar)
    return false;
  if (theVar.indexOf(subStr) !== -1)
    return true;
  return false;
}

/** Generates an almost certainly unique id for local purposes
 * 
 * @returns {String}
 */
function generateUUID() {
  var d = new Date().getTime();
  var uuid = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
    var r = (d + Math.random() * 16) % 16 | 0;
    d = Math.floor(d / 16);
    return (c == 'x' ? r : (r & 0x3 | 0x8)).toString(16);
  });
  return uuid;
}

/**
 * If you create a handler for FullCalendar 'dayClick', it just gets
 * date, jsEvent, view, [ resourceObj ] . So you want to make the date into
 * a rudimentary FCEvent object, then pass it to the same handler that
 * edits events for the 'eventClick' handler, which gets a real FCEvent obj.
 * @param date - a "Moment" date object.
 * @returns {Function}
 */

function getStringDateFromMoment(arg) {
  if (!arg) return '';
  var type = typeof(arg);
  if (type === 'string') return arg;
  if (type === 'object') { //Hope it's a Moment instance
    console.log("Arg is an object?", arg);

    var fmt1 = 'YYYY-MM-DD HH:mm:ss';
    var fmt3 = 'YYYY-MM-DD HH:mm';
    var fmt2 = 'yy-mm-dd H:m:s';
    return arg.format(fmt3);
  }
  return '';
}

function FCeventToArr(FCevent) {
  var arr = [];
  arr['title'] = FCevent.title;
 // if ((typeof(FCevent.s) )
  arr['start'] = getStringDateFromMoment(FCevent.start);
  arr['end'] = getStringDateFromMoment(FCevent.end);
  arr['id'] = FCevent.id;
  arr['allDay'] = FCevent.allDay;
  arr['backgroundColor'] = FCevent.backgroundColor;
  arr['color'] = FCevent.color;
  arr['borderColor'] = FCevent.borderColor;
  arr['textColor'] = FCevent.textColor;
  arr['event_type'] = FCevent.event_type;
  console.log("Converted Event:", arr);
  return arr;
}

/** Instantiates a jQuery dialog from a template, to edit calendar "Events"
 * from "FullCalendar"
 *
 *Assumes the name of the dialog class is: edit-event-dialog-frame 
 */
function createEventEditDialog(FCevent) {
  console.log('The event is:', FCevent);
  /*
  var strdt;
  var formats = [
    'YYYY-MM-DD HH:mm:ss',
    'YYYY-MM-dd HH:mm:ss',
    'YYYY-MM-d HH:mm:ss',
    'YYYY-MM-D HH:mm:ss'
  ];
  $.each(formats, function (i, val) {
    strdt = FCevent.start.format(val);
    console.log('From FC, fmt: ' + val + ';: strdt', strdt);

  });
  //var strdt = FCevent.start.format('YYYY-MM-DD HH:mm:ss');
  */
  $dlghtml = $('.edit-event-dialog-frame ').prop('outerHTML');
  var closeText = 'Cancel';
  var dialogDefaults = {
    modal: true,
    autoOpen: false,
    width: 600,
    buttons: [{
        text: closeText,
        click: function () {
          $(this).dialog('destroy');
        }
      }
    ]
  };


  var dlg = $($dlghtml).dialog(dialogDefaults);
  dlg.dialog('open');
  $('.hook-colorpicker').colorpicker({
    defaultPalette: 'web'
  });
  $('.hook-datetimepicker').datetimepicker({
    //timeFormat: $.timepicker.ISO_8601,
    dateFormat: 'yy-mm-dd'
    //timeFormat: $.timepicker.ISO_8601,
    //dateFormat: $.datepicker.ISO_8601
    //timeFormat: 'Y-m-d\TH:m:s',
    //dateFormat: 'Y-m-d\TH:m:s',
    //dateFormat: 'Y-m-d'
    //timeFormat: 'Y-m-d'
    //controlType: 'select'
  });
  /** Now we have to populate the dialog with the FCevent properties */
  var arr = FCeventToArr(FCevent);
  for (var key in arr) {
    dlg.find('.'+key).val(arr[key]);
  }

}

function FCeventsToArrays(FCevents) {
 var evarr = [];
 $.each(FCevents, function(idx, FCevent) {
   evarr.push(FCeventToArr(FCevent));
 });
 return evarr;

}
function ajaxPostEvents() {
  console.log("Got called from the calendar");
 var events = $('.edit-event-dialog').fullCalendar('clientEvents'); 
 console.log("The events I got back from the calendar are:", events);
 var evarr = FCeventsToArrays(events);
 console.log("Tried to fetch events and convert. They are:", evarr);

}

/*
function submitEditFCForm(data) {
  var values = {};
  $.each($('#myForm').serializeArray(), function(i, field) {
      values[field.name] = field.value;
  });

}
*/

function createFCEventObject(date) {
  console.log('The date is:', date);
  var FCevent = {start: date,
    id: generateUUID()
  };
  return createEventEditDialog(FCevent);
}

function arrToFCevent(arr) {
  var FCevent = {};
  FCevent.title = arr['title'];
  FCevent.start = arr['start'] ;
  FCevent.end = arr['end'];
  FCevent.id = arr['id']  ;
  FCevent.allDay = arr['allDay']  ;
  FCevent.backgroundColor = arr['backgroundColor'];
  FCevent.color = arr['color'] ;
  FCevent.borderColor = arr['borderColor'] ;
  FCevent.textColor = arr['textColor'] ;
  FCevent.event_type =arr['event_type'] ;
  return FCevent;
}


/** Play with FullCalendar */
$(document).ready(function () {
  $('body').on('submit', '.edit-event-dialog', function (event) {
    var form = $(event.target);
    var sa = form.serializeArray();
    var values = {};
    $.each(sa, function(i, field) {
      values[field.name] = field.value;
    });
    var FCevent = arrToFCevent(values);
    $('.edit-calendar-schedular').fullCalendar(
            'renderEvent', FCevent, true);
    console.log('sa:',sa,'values',values);
    return false;

  });
  // Try the time-picker:
  //$('.tst-timepicker').datetimepicker();
  /*
  $('.tst-colorpicker').colorpicker({
    defaultPalette: 'web'
  });
  $('.tst-timepicker').timepicker({
    controlType: 'select'
  });
  */
  $('.view-calendar-schedule').fullCalendar({
    /*
     dayClick : function(date, jsevent, view) {
     console.log('The Date Clicked on: ',date, 'And the schedular is: ',$('.calendar-schedular'));
     },
     editable:true,
     */
    height: 300,
    aspectRatio: 1.2,
    events: [
      {
        title: 'event1',
        start: '2016-04-01',
        description: "Hot summer day"
      },
      {
        title: 'event2',
        start: '2016-04-05',
        end: '2016-04-07'
      },
      {
        title: 'event3',
        start: '2016-04-09T12:30:00',
        allDay: false // will make the time show
      }
    ]
  });

  $('.edit-calendar-schedular').fullCalendar({
    editable: true,
    dayClick: createFCEventObject,
    eventClick: createEventEditDialog,
    customButtons: {
        myCustomButton: {
            text: 'Save Calendar',
            click: ajaxPostEvents
        }
    },
    header : {
        left: 'prev,next today myCustomButton',
        center: 'title',
        right: 'month,agendaWeek,agendaDay'
    },
    events: [
      {
        title: 'event1',
        start: '2016-04-01',
        description: "Hot summer day"
      },
      {
        title: 'event2',
        start: '2016-04-05',
        end: '2016-04-07'
      },
      {
        title: 'event3',
        start: '2016-04-09T12:30:00',
        allDay: false // will make the time show
      }
    ]

  });
  /*
   $('.calendar-schedular').fullCalendar({ 
   dayClick : function(date, jsevent, view) {
   console.log('The Date Clicked on: ',date, 'And the schedular is: ',$('.calendar-schedular'));
   },
   height:300,
   aspectRatio: 1.2,
   editable:true,
   events: [
   {
   title  : 'event1',
   start  : '2016-04-01',
   description : "Hot summer day"
   },
   {
   title  : 'event2',
   start  : '2016-04-05',
   end    : '2016-04-07'
   },
   {
   title  : 'event3',
   start  : '2016-04-09T12:30:00',
   allDay : false // will make the time show
   }
   ]
   });
   
   */


});
/*
 $('body').on('click', '.tst-calander-button', function (event) {
 console.log("Clicking..");
 var events = $('.calendar-schedular').fullCalendar('clientEvents'); 
 $('.calendar-schedular2').fullCalendar({ 
 height:200,
 editable:true
 });
 //  alert("Block");
 $.each(events, function (idx, obj) {
 console.log("For "+idx+' the obj',obj);
 }); 
 $('.calendar-schedular2').fullCalendar('addEventSource', events);
 //var evjson = JSON.stringify(events);
 //console.log('Client Events:', evjson);
 });
 */


  $(function() {
    $( "#tabs" ).tabs();
    //});
  });