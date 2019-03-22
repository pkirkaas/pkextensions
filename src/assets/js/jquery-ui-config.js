/**Copyight (C) 2016 by Paul Kirkaas - All Rights Reserved */
/* 
 * Generic jQuery UI Initialization - uses standardized class names to attach
 * datepicker, etc.
 */


/** Initialize Date Picker */
/*
 $(function () {
 $('input.datepicker.auto-attach').datepicker( { 
 dateFormat: 'yy-mm-dd'
 });
 });
 */
/*
$(function () {

  console.log("In Init jqui");
  var sqldt = $('input.altfield').val();
  console.log("SQL DT:", sqldt);
  var date = DateFromSql(sqldt);
  console.log("Date Obj:", date);
  $('input.datepicker.just-formatted').datepicker( {
    setDate: date,
    dateFormat: "DD, d MM, yy",
    altField: ".altfield",
    altFormat: 'yy-mm-dd',
    changeYear: true,
    yearRange: '1930:2020'
  });
  $('input.datepicker.just-formatted').datepicker('setDate',date);

  //$('input.datepicker.just-formatted').datepicker('setDate', date);
  //$('input.datepicker.just-formatted').val("Today");
});
*/
$(function () {
    var hiddenPartners = $('input.hidden-datepicker-partner');
    hiddenPartners.each(function ($index) {
      var jThis = $(this);
      var val = jThis.val();
      if (val) var date = DateFromSql(val);
      else var date = null;
      var datepicker = jThis.prev('input.nameless-proxy.datepicker');
      if (!datepicker) {
        console.log("Couldn't find the matching datepicker for ",this);
        return;
      }
      //Now we initialize the datepicker jquery object
      datepicker.datepicker({
        dateFormat: "d MM, yy",
        altField:jThis,
        altFormat: 'yy-mm-dd',
        changeYear: true,
        yearRange: '1930:2020'
      });
      datepicker.datepicker('setDate', date);
      datepicker.on('change', function(){
        if (!$(this).val()) {
          jThis.val('');
        }
      });
      
    });
    /*
    var date = DateFromSql(sqldt);
    $(this).datepicker({
      dateFormat: "DD, d MM, yy",
      altField: ".altfield",
      altFormat: 'yy-mm-dd',
      changeYear: true,
      yearRange: '1930:2020',
      setDate: date
  });
  */
});

/** Better Initialize Date Picker */
$(function () {
  $('body').on('focus', 'input.datepicker.auto-attach', function (e) {
    $(this).datepicker({
      dateFormat: 'yy-mm-dd',
      changeYear: true,
      yearRange: '1930:2020'
    });
  });
});



// Consider: https://www.html5andbeyond.com/jquery-ui-datepicker-and-timepicker/
//Consider: https://github.com/trentrichardson/jQuery-Timepicker-Addon (Used before, in therapy)
// Currently: http://jonthornton.github.io/jquery-timepicker/ (as of 5/17, Regina's Mental Health
/** Better Initialize Time Picker */
$(function () {
  $('body').on('focus', 'input.timepicker.auto-attach', function (e) {
    $(this).timepicker({
      timeFormat: 'H:i',
      minTime: '8',
      maxTime: '22'
    });
  });
});
/*
 $('body').on('focus', 'input.timepicker.auto-attach', function (e) {
 $(this).timepicker({
 timeFormat: 'HH:mm:ss',
 interval: 60,
 minTime: '10',
 maxTime: '6:00pm',
 defaultTime: '11',
 startTime: '10:00',
 dynamic: false,
 dropdown: true,
 scrollbar: true
 });
 });
 */
/** Persist current tab and return to it - but clear if doesn't exist in this
 * page
 */
$(function () {
  var navsel = 'ul.nav-tabs li.nav-item a.nav-link'; //The tab link cicked on
  $('body').on('click', navsel, function (event) {
    localStorage.setItem('lastTab', $(event.target).attr('href'));
  });
  //Retrieve localStorage lastTab - if it exists on page, go to it, else reset
  var lastTab = localStorage.getItem('lastTab');
  if (lastTab) {
    var setActiveSel = navsel + '[href="' + lastTab + '"]';
    var setActive = $(setActiveSel);
    if (!setActive.length) {
      localStorage.removeItem('lastTab');
    } else {
      setActive.tab('show');
    }
  }
});


/** Enable all tooltips */
$(function () {
  $('[data-toggle="tooltip"]').tooltip({
    html: true
  });
  $('[data-toggle="tooltip"]').tooltip('show');
});


//This bit is actually BS 4 - to ADD hover to drop-down menus. Has CSS also

$('body').on('mouseenter mouseleave','.dropdown',function(e){
  var _d=$(e.target).closest('.dropdown');_d.addClass('show');
  setTimeout(function(){
    _d[_d.is(':hover')?'addClass':'removeClass']('show');
  },300);
});

