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

/** Better Initialize Date Picker */
$('body').on('focus', 'input.datepicker.auto-attach', function (e) {
  $(this).datepicker({
    dateFormat: 'yy-mm-dd',
    changeYear: true,
    yearRange: '1930:2020'
  });
});


// Consider: https://www.html5andbeyond.com/jquery-ui-datepicker-and-timepicker/
//Consider: https://github.com/trentrichardson/jQuery-Timepicker-Addon (Used before, in therapy)
// Currently: http://jonthornton.github.io/jquery-timepicker/ (as of 5/17, Regina's Mental Health
/** Better Initialize Time Picker */
$('body').on('focus', 'input.timepicker.auto-attach', function (e) {
  $(this).timepicker({
    'timeFormat': 'H:i'
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


