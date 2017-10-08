/* JS for Social Functionality Support - ratings, messaging, blocking, etc.  */

/* Makes the "Send Message" dialog box / form */
//$('body').on('click', 'div.pkmvc-button.js-contact', function (event) {
$(function () {
$('body').on('click', '.js-contact', function (event) {
    var tpl = $('.template-container .send-message-dialog').first().prop('outerHTML');
    var user_id_to = $(event.target).attr('data-user_id_to');
    var user_name = $(event.target).attr('data-user_name');
    tpl = tpl.replace(/__CNT_TPL__/g, user_id_to);
    tpl = tpl.replace(/__USR_NAME__/g, user_name);
    var defaultWidth = Math.min(600,$(window).width());
		$(tpl).dialog( {
      modal : true,
      minWidth: defaultWidth,
      dialogClass : 'col-sm-8 dialog-responsive',
      resizable : true,
      buttons : {
        //Cancel : function () { $(this).dialog('close'); }
        Cancel : function () { $(this).dialog('destroy'); }
      }
    });
  });

$('body').on('click', 'div.blocked-toggle', function (event) {
    var them_id = $(event.target).attr('data-them_id');
    var data = {them_id: them_id};
    console.log('data',data);
    var res = $.ajax({
      url: ajaxUrlToggleBlocked,
      data: data,
      method: 'POST'
    }).done(function (data) {
      console.log('After Ajax Data',data);
      if (data == 'blocked') {
        $(event.target).addClass('blocked');
        $(event.target).removeClass('unblocked');
      } else if  (data == 'unblocked') {
        $(event.target).addClass('unblocked');
        $(event.target).removeClass('blocked');
      }
    });
  });



// Delete Msg
$('body').on('click', 'div.js-delete-el.del-msg', function (event) {
    var message_id = $(event.target).attr('data-message_id');
    var data = {message_id: message_id};
    console.log('data',data);
    var res = $.ajax({
      url: ajaxUrl + '?action=delete-msg',
      data: data,
      method: 'POST'
    }).done(function (data) {
      console.log('After Ajax Data',data);
      if (data === true) { 
        $(event.target).closest('div.deletable-data-set').remove();
      }
    });
  });
// End Del Msg


// Delete Conversation
$('body').on('click', 'div.js-delete-el.del-conversation', function (event) {
    var them_id = $(event.target).attr('data-them_id');
    var data = {them_id: them_id};
    var res = $.ajax({
      url: ajaxUrl + '?action=delete-conversation',
      data: data,
      method: 'POST'
    }).done(function (data) {
      console.log('After Ajax Data',data);
      if (data === true) { 
        $(event.target).closest('div.deletable-data-set').remove();
      }
    });
  });
// End Del Msg

$('body').on('click', 'div.favorite-toggle', function (event) {
    var them_id = $(event.target).attr('data-them_id');
    var data = {them_id: them_id};
    console.log('data',data);
    var res = $.ajax({
      url: ajaxUrlToggleFavorite,
      data: data,
      method: 'POST'
    }).done(function (data) {
      console.log('After Ajax Data',data);
      if (data == 'favorited') {
        $(event.target).addClass('favorited');
        $(event.target).removeClass('unfavorited');
      } else if  (data == 'unfavorited') {
        $(event.target).addClass('unfavorited');
        $(event.target).removeClass('favorited');
      }
    });
  });
});




