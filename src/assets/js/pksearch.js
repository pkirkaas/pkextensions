/* 
 * Start of supporting Search Forms. Depends on jQuery & pklib.js (included first)
 * Expected Structure:
 * Every search form has the CSS class "search"
 * Every pair of Criteria/Value controls are in a CSS class 'search-crit-val-pair'
 * Every search criteria control has the CSS class 'search-crit'
 * Every search value control has the CSS class 'search-val'
 */


$(function () {
  console.log("In Init");
  disableDontCares();
  $('body').on('change', 'form.search .search-crit', function (event) {
    console.log("Search Crit changed");
    var target = event.currentTarget;
    disableDontCare(target);
  });

});


/** For all Search Criteria Controls that are set to "Don't Care", disable
 * matching Search Value control
 */
function disableDontCares() {
  console.log("In disableDontCares");
  $('form.search .search-crit').each( function() {
      disableDontCare(this);
  });
}

/** If target is a 'search-crit' control, and equal to 'Don't Care', disable
 * its paired 'search-val' control. Else, enable
 * @param search criteria control target
 */
function disableDontCare(target) {
  var paired_val_ctl = getCousins('.search-crit-val-pair','.search-val',target);
  var search_crit_val = $(target).val();
  // if (!search_crit_val) Doesn't work???
  if (search_crit_val == 0) {

    if ($(paired_val_ctl).is('input[type="checkbox"]')) {
      $(paired_val_ctl).prop('checked',false);
    } else {
      $(paired_val_ctl).val('');
    }
    $(paired_val_ctl).attr('disabled',true);
    $(paired_val_ctl).attr('title','Select a criteria to enter a value');
    $(paired_val_ctl).addClass('disabled');
    $(paired_val_ctl).closest('div.multiselect').addClass('disabled');
    $(paired_val_ctl).removeClass('enabled');
  } else {
    $(paired_val_ctl).attr('title','');
    $(paired_val_ctl).attr('disabled',false);
    $(paired_val_ctl).removeClass('disabled');
    $(paired_val_ctl).closest('div.multiselect').removeClass('disabled');
    $(paired_val_ctl).addClass('enabled');
  }
}

