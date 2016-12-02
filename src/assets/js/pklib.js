var repository = {};
/** Library of convenient JS/jQuery functions. Not PKMVCFramework Specific
 * @author    Paul Kirkaas
 * @email     p.kirkaas@gmail.com
 * @copyright Copyright (c) 2012-2014 Paul Kirkaas. All rights Reserved
 * @license   http://opensource.org/licenses/BSD-3-Clause  
 */

/** If need to do something on element create */
/*
$('body').on('DOMNodeInserted',  function (e) {
  if ($(e.target).is(selector)) {
    //Do Something
  }
  var dpel = $(e.target).find(selector);
  if (dpel.length) {
    //Do Something
  }
});
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

/** As above, except the template is encoded in the create button itself, in
 * the data-template attribute
 */
  $('body').on('click', '.js.btn.create-new-data-set-int', function (event) {
    //Get a copy of the template element
    //var tpl = $(event.target).closest('div.templatable-data-sets').find('.template-container').first().html();
    var tpl=$(event.target).attr("data-template");
    console.log("New TPL: ",tpl);

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

////// Some Togglers
/** Toggle enabled/disabled other form components
 * The 'toggling' class: enable-toggler
 * The 'toggled' class: enable-toggled
 * Goes up DOM until 'body' OR '.enable-toggle-set', then disables every control
 * of class 'enable-toggled' below that.
 */
$('body').on('click', '.enable-toggler', function (event) {
  var toggled = $(event.target).closest('.enable-toggle-set, body').find('.enable-toggled');
  var disabled = toggled.prop('disabled');
  toggled.prop('disabled', !disabled);
});

/* Toggle show/hide - Toggler class: 'hide-toggler', target class: 'hide-toggled', wrapper: 'hide-toggle-set'
 * 
 */
$('body').on('click', '.hide-toggler', function (event) {
  var toggled = $(event.target).closest('.hide-toggle-set, body').find('.hide-toggled');
  //console.log("enable-toggled:",toggled[0]);
  var disabled = toggled.prop('disabled');
  toggled.prop('disabled', !disabled);
});


/** Set subform select inputs after templating */


$(function () {
    var tpl_sels = $('.templatable-data-sets select');
    tpl_sels.each(function(idx, sel_el) {
      var jqSelEl = $(sel_el);
      var selected = jqSelEl.attr('selected');
      var data_selected = jqSelEl.attr('data-selected');
      //Using attr('selected') On Purpose, since built by templater that way
      if ((typeof data_selected) !== 'undefined') {
    //    console.log('SETTING Selected',selected,'data-selected',data_selected,'sel_el',sel_el);
        jqSelEl.val(data_selected);
      }
    });


});

/**
 * In CSS, do:
 * 

@media print {
  .print-button { display: none; }
  .no-print { display: none; }
}
 */
  $('body').on('click', '.print-button', function (event) {
    window.print();
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




/** Generic attachment of AJAX call to DOM element (button?)
 * Attaches to any DOM element with CSS data attribute of 'data-pk-ajax-element'.
 * The 'data-pk-ajax-element' has one required data-attribute: 'data-ajax-url'.
 * Optional data-attributes:
 *  'data-func-target' : string - Name of a JS function to call with the AJAX response
 *  'data-attr-target' : string - Name of the HTML attribute to set w. AJAX resp
 *  'data-selector-target' : string - CSS Selector of this or descendent DOM
 *     element to set innerHTML with result of AJAX call.
 *  'data-func-arg': optional JSON encoded scalar or array to use with AJAX return to build response
 *      For Function targets, function called with <tt>func_target(clickTarget, ajax_response, func_arg);</tt>
 *  'data-attr-arg': optional JSON encoded scalar or array to use with AJAX return to build response
 *     
 *  'data-selector-arg': optional JSON encoded scalar or array to use with AJAX return to build response 
 *     with the AJAX response object, and pass as a single OBJ arg to the function target
 *  'data-ajax-params' : url-encoded argument/query string
 * @param {type} menu
 * @returns {undefined}
 */

/*
 $(function () {
 $('body').on('click', '[data-pk-ajax-element]', function (event) {
 alert("Jesus God!");
 });
 });
 */

$(function () {
  $('body').on('click', '[data-pk-ajax-element]', function (event) {
    var target = $(event.target);
    var ajax_url = htmlDecode(target.attr('data-ajax-url'));
    var ajax_params = htmlDecode(target.attr('data-ajax-params'));
    var ajax_data = parseStr(ajax_params);
    //console.log('raw target args:', target.attr('data-response-target-arg'));

    var func_arg = JSON.parse(target.attr('data-func-arg'));
    var attr_arg = JSON.parse(target.attr('data-attr-arg'));
    var selector_arg = JSON.parse(target.attr('data-selector-arg'));
    var selector_target = target.attr('data-selector-target');
    var attr_target = target.attr('data-attr-target');
    var func_target = target.attr('data-func-target');

/*
    console.log('ajax_url: ', ajax_url, ' -- ajax_params: ', ajax_params, ' -- ajax_data: ', ajax_data,
            '-func_arg: ', func_arg, ' Type FA:', typeof (func_arg), '-func_target: ', func_target,
            '-selector_arg: ', selector_arg, ' Type SA:', typeof (selector_arg), '-selector_target: ', selector_target,
            '-attr_arg: ', attr_arg, ' Type AA:', typeof (attr_arg), '-attr_target: ', attr_target
            );
    */
    var res = $.ajax({
      url: ajax_url,
      data: ajax_data,
      method: 'POST'
    }).done(function (data) {
      if (func_target && (typeof(window[func_target]) === 'function') ) {
        window[func_target](target, data, func_arg);
      }
      if (attr_target) {
        if (typeof (attr_arg) === 'object') {
          if (typeof (data) !== 'object') { //Use AJAX data return as key to arg
            var attr_content = attr_arg[data];
          } else {
            console.log("AJAX data & selector_arg both objects - what to do? DATA: ",
                    data, " -- SEL ARG: ", selector_arg);
            var attr_content = false;
          }
        } else { //selector_arg is scalarish...
          if (typeof (data) === 'object') { //Try using selector arg as key to AJAX return?
            var attr_content = data[attr_arg];
          } else { //Both selector_arg & AJAX return data are scalarish - concat
            var attr_content = data + ' ' + selector_arg;
          }
        }
        if (attr_content !== false) {
          target.prop(attr_target, htmlEncode(attr_content));
        }
      }
      if (selector_target) {
        if (typeof (selector_arg) === 'object') {
          if (typeof (data) !== 'object') { //Use AJAX data return as key to arg
            var sel_content = selector_arg[data];
          } else {
            console.log("AJAX data & selector_arg both objects - what to do? DATA: ",
                    data, " -- SEL ARG: ", selector_arg);
            var sel_content = false;
          }
        } else { //selector_arg is scalarish...
          if (typeof (data) === 'object') { //Try using selector arg as key to AJAX return?
            var sel_content = data[selector_arg];
          } else { //Both selector_arg & AJAX return data are scalarish - concat
            var sel_content = data + ' ' + selector_arg;
          }
        }
        if (sel_content !== false) {
          if (target.is(selector_target))
            target.html(sel_content);
          else if (target.find(selector_target).first().length) {
            target.find(selector_target).first().html(sel_content);
          }
        }
      }
    });
  });
});


jQuery.fn.extend({
  htmlFormatted: function (content) {
    //console.log("Formatters:", this.formatters);
    for (var formatter in this.formatters) {
      if (this.formatters[formatter](this,content)) return true;
    }
    this.html(content);
    },
  formatters: {
    currency: function(jqobj,content) {
      if (jqobj.hasClass('jq-format-currency')) {
        var num = toNumber(content);
        if (isNaN(num)) {
          console.error('Invalid Number: ',content);
          jqobj.html('');
        }
        var wrap_class = jqobj.attr('data-wrap-class');
        if (wrap_class) {
          jqobj.addClass(wrap_class);
        } else {
          jqobj.addClass('pk-dollar-value');
          jqobj.addClass('dollar-format');
        }
        var sign = '';
        if (num < 0) {
          jqobj.addClass('negative-dollar-value');
          num = -num;
          sign = '-';
        }
        var currency =sign+'$'+num.toLocaleString("en"); 
        jqobj.html(currency);
        return true;
      }
      return false;
    }
  },
  addFormatter: function(name, formatter) {
    jQuery.formatters[name] = formatter;
  }
});

function demoResponseTarget(target, data, arg) {
  //console.log("DemoResponseTarget ARGS: ", data, arg, 'target: ', target);
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

/** Deletes the closest parent that matches the selector
 * To delete a row/subform in a multi-row form
 * @param node element - node - usually the button clicked on
 * @param string selector - jquery/css selector
 */
function deleteClosest(element, selector) {
  $(element).closest(selector).remove();

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
  if (isjQuery(arg)) {
  //if (arg instanceof jQuery) {
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

function FormattedHtml(element, content) {
  element = jQuerify(element);
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

/*
$('body').on('click', 'img.avatar', function (event) {
  //console.log("Clicking..");
});
$('body').on('hover', 'img.avatar', function (event) {
  //console.log("Hovering..");
});
*/

$(function () {
  var dlgonload = $('.dialog-on-load');
  //console.log("Loaded...");
  if (dlgonload && dlgonload.hasClass('js-dialog-content')) {
    //console.log("We found a dialog on load!");

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


/** This is the generic Dialog Launcher. It attaches to every element
 * with CSS class: js-dialog-button, and launches a display:none; HTML
 * Dialog element, with customized parameters - 
 * 
 * js-dialog-button can be any type of element (like img), and multiple per page.
 * Options / data-params for js-dialog-button:
 * 
 * The clickable button/target/launcher at minimum should be:
 * 
 * CSS Class: 'js-dialog-button'
 * 
 * Have the data attribute: 'data-dialog', value: Same as the 'data-dialog'
 * element of the display:none template.
 * 
 * Optional Attributes:
 * data-param1, data-param2, data-param3 - the value of these attributes is just
 * substituted into the template strings __TPL_PARAM1__, etc, in the hidden template
 * 
 * The Dialog Template: Should have CSS class: 'js-dialog-content'
 *     (which should be defined as "display:none;" in CSS
 * Should have the data attribute: data-dialog=[same as the data-dialog value
 * of the button/element launching it]
 * 
 * Optionally the strings __TPL_PARAM1__ -> 3, replaced as above.
 * 
 * Optionally, any of the following options to jQuery dialog, preceded
 * by data- (eg, data-modal='true')
   jQuery Dlg Options: 'modal', 'autoOpen', 'buttons', 'closeOnEscape',
    'dialogClass', 'title', 'minHeight', 'minWidth', 'width', 'height';
 * 
 * Example: Button/Clickable:
 * 
 * <img class="resp-gal-img  img-fluid js-dialog-button "
 * data-dialog="big-picture-dialog" data-src-sel="img"
 * data-src-attr="src"
 * src="http://lkirkaas.local/cmp_gallery/93-ATreeGrowsInAfrica.jpg">
 * 
 * Example Dialog Template:
 * <div class='js-dialog-content' data-dialog='big-picture-dialog'
 *  data-width='900'
 *  data-title='The Big Picture'>
 *      <img src='__TPL_PARAM1__' class='big-picture-dialog'>
 *     <div class="image-desc">__TPL_PARAM2__</div>
 * </div>
 */

$('body').on('click', '.js-dialog-button', function (event) {
  var src = $(event.target).attr('data-dialog');
  if (!src) return;
  var param1 = $(event.target).attr('data-param1') || '';
  var param2 = $(event.target).attr('data-param2') || '';
  var param3 = $(event.target).attr('data-param3') || '';
  //var clone = $(event.target).attr('data-clone');
  var clone = true;
  var dlg = $('.js-dialog-content[data-dialog="' + src + '"]');
  if (dlg.length === 0)
    return;
  var dlgHtml = dlg.prop('outerHTML');
  dlgHtml = dlgHtml.replace(/__TPL_PARAM1__/g, param1);
  dlgHtml = dlgHtml.replace(/__TPL_PARAM2__/g, param2);
  dlgHtml = dlgHtml.replace(/__TPL_PARAM3__/g, param3);
  //console.log("After all the replacements, dlgHtml:",dlgHtml);
  dlg = $(dlgHtml);
  //else  console.log('clone was false', clone);
  //opts.title = dlg.attr('data-title');
  var closeText = $(event.target).attr('data-closetext');
  if (!closeText) closeText = dlg.attr('data-closetext');
  if (!closeText) closeText = 'Okay';
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
  //console.log('dialogOptions', dialogOptions);
  dlg.dialog(dialogOptions);
  dlg.title = function (title) {
    dlg.dialog('option', 'title', title);
  };
  //dlg.dialog('option','dialogClass', dialogClass);
  dlg.dialog('open');
  return dlg;

});

//Do similar for Bootstrap 4 Modals/Dialogs
$('body').on('click', '.bs4-dialog-button', function (event) {
  var src = $(event.target).attr('data-bs4-dialog');
  if (!src) return;
  var param1 = $(event.target).attr('data-param1') || '';
  var param2 = $(event.target).attr('data-param2') || '';
  var param3 = $(event.target).attr('data-param3') || '';
  //var clone = $(event.target).attr('data-clone');
  var clone = true;
  var dlg = $('.bs4-dialog-content[data-bs4-dialog="' + src + '"]');
  if (dlg.length === 0)
    return;
  var dlgHtml = dlg.prop('outerHTML');
  dlgHtml = dlgHtml.replace(/__TPL_PARAM1__/g, param1);
  dlgHtml = dlgHtml.replace(/__TPL_PARAM2__/g, param2);
  dlgHtml = dlgHtml.replace(/__TPL_PARAM3__/g, param3);
  //console.log("After all the replacements, dlgHtml:",dlgHtml);
  dlg = $(dlgHtml);
  dlg.modal();
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
  //console.log("OverridableOpts", overridableOptions, 'opts', opts, 'DialogOpts', dialogOptions);
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

/** To Pop Up a dialog box on Window Load, if it exists (Version 1. V2 below) */
$(function () {

  var dbx = $('.jqui-dlg-pop-load');
  if(!dbx.length) return;
  closeText = dbx.attr('data-closetext');
  var title = dbx.attr('data-title'); 
  var dialogClass = dbx.attr('data-dialogClass'); 
  var dialogDefaults = {
    modal: true,
    autoOpen: true,
    minWidth: 800,
    resizable: true,
    draggable: true,
    title: title,
    dialogClass: dialogClass,
    buttons: [{
        text: closeText,
        click: function () {
          $(this).dialog('close');
        }
      }
    ]
  };
  $('.jqui-dlg-pop-load').dialog(dialogDefaults);
});



/** jqui-dlg-pop-load-wrapper is JUST the wrapper - the dialog is within
 * So all the data-XXX attributes for the dlg box should be in a div WITHIN
 * the jqui-dlg-pop-load-wrapper div
 */
$(function () {
  var $dlgwrap = $('.jqui-dlg-pop-load-wrapper');
  if (!$dlgwrap.length) return;
  $dlgwrap.hide();
  console.log("Wrapper found & hidden - looking for first child...");
  //var $dbx = $dlgwrap.find(':first-child');
  var $dbx = $dlgwrap.find('div').first();
  console.log("First Child:",$dbx[0],'Size:',$dbx.length);
  if(!$dbx.length) return;
  closeText = $dbx.attr('data-closetext') ? $dbx.attr('data-closetext')  : 'Close';
  var title = $dbx.attr('data-title'); 
  var dialogClass = $dbx.attr('data-dialogClass');
  var dialogDefaults = {
    modal: true,
    autoOpen: true,
    minWidth: 600,
    resizable: true,
    draggable: true,
    title: title,
    dialogClass: dialogClass,
    buttons: [{
        text: closeText,
        click: function () {
          $(this).dialog('close');
        }
      }
    ]
  };
  $dbx.dialog(dialogDefaults);
});


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

/***************   Starts of useful functions - not robust yet ********/
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
function in_array(key, arr) {
  var ktype = typeof (key);
  var res = false;
  $.each(arr, function (idx, val) {
    vtype = typeof (val);
    if (key === val) {
      res = true;
    }
  });
  return res;
}

/** Supposedly jQuery functions to safely html encode/decode text (html)
 * to store in a data-attribute
 */
function htmlEncode(value) {
  if (value) {
    return jQuery('<div />').text(value).html();
  } else {
    return '';
  }
}

function htmlDecode(value) {
  if (value) {
    return $('<div />').html(value).text();
  } else {
    return '';
  }
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


/**
 * Returns associative array of named "GET" parameters and values
 */
function getGets() {
  var queryStr = window.location.search.substr(1);
  return parseStr(queryStr);
}

/** A VERY light weight version of PHP parse_str - converts a simple
 * HTTP query string (key1=val1&key2=val2..) to a JS Obj 
 * @param string queryStr
 * @returns object
 */
function parseStr(queryStr) {
  var params = {};
  if (!queryStr || (queryStr === ''))
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

	 
  /** This section intended to highlight a searchterm and scroll down to it
   * if GET param 'search_term=search term' exists in URL.
   * Not sure if this is the best way/place to do it?
   */	
  var gets = getGets();
  if (gets.search_term && (typeof gets.search_term === 'string') && gets.search_term.length) {
    //We have a search term - do we have a dom '.entry-wrapper' element?
    var search_in = $('.entry-wrapper');
    var search_term = decodeURIComponent(gets.search_term);
    if (search_term && (typeof search_term === 'string') && search_in.length) {
      var spanClass = 'highlight-matched-searchterm';
      var spanSelector = '.'+spanClass;
      //console.log('SI Exists & Search Term: ', search_term, 'SI:', search_in);
      var starr = safeSplitString(search_term);
      //console.log('Search Term Array: ', starr);
      var bodyHtml = search_in.html();
      for (var ix = 0 ; ix < starr.length ; ix++) {
        var cmpstr = starr[ix];
        cmpRE = new RegExp(cmpstr,'ig');
        bodyHtml = bodyHtml.replace(cmpRE,'<span class="'+spanClass+'">$&</span>');
      }
      search_in.html(bodyHtml);
      if ($(spanSelector).length) {
        var topOffset =  $(spanSelector).offset().top - 120; 
        //console.log("TopOffset:", topOffset);
        window.scrollTo(0, topOffset);
      }
    }
  }	

//////// Supporting Functions to be made more robust ///////


/**
 * Split a string into an array of SAFE string components
 * @param string thestring
 * @param string splitchar - default ' '
 * @returns Array - of hopefully safe string components
 */
function safeSplitString(thestring,splitchar) {
// Is this really safe? At least a sinlge place to fix... 
  if (!thestring || !(typeof thestring === 'string') || !thestring.length) {
    return [];
  }
  splitchar = splitchar || ' ';
  var rawSplitArr = thestring.split(splitchar);
  var retArr = [];
  for (var i = 0 ; i < rawSplitArr.length ; i ++) {
    retArr[i] = encodeURIComponent(rawSplitArr[i]);
  }
  return retArr;
}


/** If a form is modified, makes user confirm to leave, unless action is
 * to submit
 * @returns {undefined}
 */
//Don't want to make this default for ALL forms!
/**
$(function () {
  $('body').on('change', 'form', function (event) {
    $(window).on('beforeunload', function (event) { 
      var confirmationMessage = "Unsaved Changes";
      event.preventDefault();
      event.returnValue = confirmationMessage; 
      return confirmationMessage;
    });
  });
  $('form').submit (function (event) {
    console.log("Got in onload - trying 'off' ...");
    $(window).off('beforeunload');
  });
});
*/
//Just forms with class '.chck-frm'
/*
*/
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
    //console.log("Got in onload - trying 'off' ...");
    $(window).off('beforeunload');
  });
});

function isObject(obj) {
  return obj === Object(obj);
}

/** Returns true for false/undefined/null/0/''/{}/[]
 * 
 * @param mixed avar
 * @returns boolean - true if 'empty', else false
 */
function isEmpty(avar) {
  return (avar === undefined) || !avar || (isObject(avar) && !Object.keys(avar).length);
}
function isjQuery(obj) {
  return (obj && (obj instanceof jQuery || obj.constructor.prototype.jquery));
}

/** Returns the maximum outerHeight of elements in avar
 * 
 * @param mixed avar - can be a jQuery collection, selector, or HTML string
 * @returns int maxheight
 */
function maxHeight(avar) {
  var els = jQuerify(avar);
  var lmaxHeight = 0;
  els.each(function() {
    if ($(this).outerHeight() > lmaxHeight) {
        lmaxHeight = $(this).outerHeight();
    }
  });
  return lmaxHeight;
}
/*
function isjQuery(avar) {
  return isObject(avar) && (avar instanceof jQuery);
}
*/

/**
 * Is the argument an integer or string convertable to an int?
 * isIntish('7') true
 * isIntish('7 8') false
 * isIntish([7]) true - not what I want
 * @param mixed value
 * @returns boolean
 */
function isIntish(value) {
  return !isNaN(value) &&
          parseInt(Number(value)) == value && //if use ===  isIntish('7') false!
          !isNaN(parseInt(value, 10));
}

/** Returns a number if that's reasonable, else NaN. */
function toNumber(aval) {
  if (!isNumeric(aval)) return NaN;
  return Number(aval);
}

/**
 * For values:
   '7' => true
   7.9 => true
   false => false
   true => false
   null => false
   [6] => false
   ['7'] => false
   '7 8 9' => false
   {0:17} => false 
 * @param mixed aval
 * @returns boolean - true if reasonably numeric, else false (as above)
 */
function isNumeric(aval) {
  return (! (aval instanceof Array)) && (Number(aval)===parseFloat(aval)); 
}

function isArray(aval) {
  return aval instanceof Array;
}


/** 
 * Takes an associative array of key/value pairs and returns a GET param str
 * TODO: URL encode? But what if existing param values are already URLencoded?
 * @param Array getArr: array of key/value pairs
 * @returns String query get parameter string
 */
function setGets(getArr) {
}
