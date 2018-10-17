/* 
 * Experimental handler for the fullcalendar jQuery UI Plugin
 */



/**
 * If you create a handler for FullCalendar 'dayClick', it just gets
 * date, jsEvent, view, [ resourceObj ] . So you want to make the date into
 * a rudimentary FCEvent object, then pass it to the same handler that
 * edits events for the 'eventClick' handler, which gets a real FCEvent obj.
 * @param date - a "Moment" date object.
 * @returns {Function}
 */

function getStringDateFromMoment(arg) {
  if (!arg)
    return '';
  var type = typeof (arg);
  if (type === 'string')
    return arg;
  if (type === 'object') { //Hope it's a Moment instance
    var fmt3 = 'YYYY-MM-DD HH:mm';
    return arg.format(fmt3);
  }
  return '';
}

function FCeventToArr(FCevent) {
  var arr = {};
  arr.guid =FCevent.guid; 
  if (!(FCevent.guid)) {
    arr.guid = generateUUID();
  }
  arr.title = FCevent.title;
  // if ((typeof(FCevent.s) )
  arr.start = getStringDateFromMoment(FCevent.start);
  arr.end = getStringDateFromMoment(FCevent.end);
  arr.id = FCevent.id;
  arr.allDay = FCevent.allDay;
  arr.backgroundColor = FCevent.backgroundColor;
  arr.color = FCevent.color;
  arr.borderColor = FCevent.borderColor;
  arr.textColor = FCevent.textColor;
  arr.event_type = FCevent.event_type;
  return arr;
}

/** Instantiates a jQuery dialog from a template, to edit calendar "Events"
 * from "FullCalendar"
 *
 *Assumes the name of the dialog class is: edit-event-dialog-frame 
 */
function createEventEditDialog(FCevent) {
  //console.log("Entering createEvent, FCevent:", FCevent);
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
    dateFormat: 'yy-mm-dd'
  });
  /** Now we have to populate the dialog with the FCevent properties */
  var arr = FCeventToArr(FCevent);
  for (var key in arr) {
    dlg.find('.' + key).val(arr[key]);
  }

}

function FCeventsToArrays(FCevents) {
  var bevarr = [];
  var singleEv;
  $.each(FCevents, function (idx, FCevent) {
    singleEv = FCeventToArr(FCevent);
    bevarr.push(singleEv);
  });
  return bevarr;
}


function    regesterEventsIndividually(decoded) {
  var res;
  //console.log('Entered individualEvent Reg');
  $.each(decoded, function (idx, val) {
    if (!val.title) {
      //console.log('Creating a title');
      val.title = generateUUID();
    }
    res = $('.edit-calendar-schedular').fullCalendar('renderEvent', val, true);
    //console.log("In individual loads: " + idx + " the val is:", val, ' The result was: ', res);

  });
}



function ajaxFetchEvents() {
  /*
   if (repository.fetchedEvents == 1) {
   console.log ("Already fetched calendar Events");
   return;
   }
   */
  //console.log('Fetching events');
  var model_id = $('.edit-calendar-schedular').attr('data-modelid');
  var fetchUrl = $('.edit-calendar-schedular').attr('data-fetchurl');
  if (!fetchUrl || !model_id) {
    console_log("Could't find the parameters for the fetch");
  }
  $.ajax({
    url: fetchUrl,
    data: {model_id: model_id},
    dataType: 'json',
    method: 'POST',
    success: function (result) {
        //console.log("The AJAX FETCH result:", result);
     // var decoded = JSON.parse(result);
      ///console.log("The decoded FETCH result:", decoded);
      //$('.edit-calendar-schedular').fullCalendar('addEventSource', decoded);
      //regesterEventsIndividually(decoded);
      //if (decoded && result) {
        //console.log("We decided the reusult was okay");
        //     repository.fetchedEvents = 1;
      //} else {
       // console.log("We ran the AJAX fetch, but didn't get useful results");
     // }
    }
  });
}


function ajaxPostEvents() {
  //console.log("Got called from the calendar");
  var events = $('.edit-calendar-schedular').fullCalendar('clientEvents');
  //console.log("The events I got back from the calendar are:", events);
  var evarr = FCeventsToArrays(events);
  //console.log("Tried to fetch events and convert. They are:", evarr);
  // Get the URLs and Model ID from the Calendar frame
  var model_id = $('.edit-calendar-schedular').attr('data-modelid');
  //evarr['model_id'] = model_id;
  var fetchUrl = $('.edit-calendar-schedular').attr('data-fetchurl');
  var saveUrl = $('.edit-calendar-schedular').attr('data-saveurl');
  //console.log("Our m/f/s vals are:", model_id, fetchUrl, saveUrl);
  var jsonEvents = JSON.stringify(evarr);
  //console.log("The Stringiried json:", jsonEvents);
  $.ajax({
    url: saveUrl,
    data: {jsonEvents: jsonEvents, model_id: model_id},
    dataType: 'json',
    method: 'POST',
    success: function (result) {
      //console.log("The AJAX result:", result);
    }
  });
}


function createFCEventObject(date) {
  //console.log('The date is:', date);
  //ajaxFetchEvents();
  var FCevent = {start: date,
    id: generateUUID()
  };
  return createEventEditDialog(FCevent);
}

function deleteDupEvents(obj) {
  var ttype = typeof(this.guids);
  if (typeof(this.guids) === 'undefined') {
    //console.log("Type",ttype);
    this.guids = [];
  }
  var tmpguid = obj.guid;
  //console.log("Examining:", obj, 'guids', this.guids, 'tmp', tmpguid);
  if (!(obj.guid)) { console.log("Deleting because no GUID"); return true;}
  //if (($.inArray(tmpguid, this.guids))!== -1) { console.log("Deleting because in GUIDS ARRAy",tmpguid, this.guids); return true;} 
  if (in_array(tmpguid, this.guids)) { console.log("Deleting because in GUIDS ARRAy",tmpguid, this.guids); return true;} 
  this.guids.push(tmpguid);
  //console.log('We keep this one:', obj);
  return false;
}
$(document).ready(function () {
  $('body').on('click', '.delete-events', function (event) {
    //$('.edit-calendar-schedular').fullCalendar('removeEvents', function(event){ return true;});
    $('.edit-calendar-schedular').fullCalendar('removeEvents', deleteDupEvents);
  });
  $('body').on('click', '.fetch-events', function (event) {
    //console.log('Asking Calendar to fetch');
    $('.edit-calendar-schedular').fullCalendar('refetchEvents');
  });
  $('body').on('click', '.set-event-src', function (event) {
            //'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    var model_id = $('.edit-calendar-schedular').attr('data-modelid');
    var fetchUrl = $('.edit-calendar-schedular').attr('data-fetchurl');
    var jsonSrc = {
      url : fetchUrl,
      type : 'POST',
      data : {
        model_id : model_id,
        _token  : $('meta[name="csrf-token"]').attr('content')
      }, 
      error : function () {alert ("There was an error fetching");}
    };
    //console.log('Setting the JSON Event Src to: ', jsonSrc);
    $('.edit-calendar-schedular').fullCalendar('addEventSource', jsonSrc);

  });
});

function arrToFCevent(arr) {
  var FCevent = {};
  FCevent.guid = arr['guid'];
  if (!arr['guid']) {
    FCevent.guid = generateUUID();
  }
  FCevent.title = arr['title'];
  FCevent.start = arr['start'];
  FCevent.end = arr['end'];
  FCevent.id = arr['id'];
  FCevent.allDay = arr['allDay'];
  FCevent.backgroundColor = arr['backgroundColor'];
  FCevent.color = arr['color'];
  FCevent.borderColor = arr['borderColor'];
  FCevent.textColor = arr['textColor'];
  FCevent.event_type = arr['event_type'];
  return FCevent;
}

function forceRender() {
  $('.edit-calendar-schedular').fullCalendar('render');
}

/** Play with FullCalendar */
$(document).ready(function () {
  $('body').on('submit', '.edit-event-dialog', function (event) {
    var form = $(event.target);
    var sa = form.serializeArray();
    var values = {};
    $.each(sa, function (i, field) {
      values[field.name] = field.value;
    });
    if (!values.guid) values.guid =  generateUUID();
    //if (!values.guid) console.log("I just assigned it");
    var FCevent = arrToFCevent(values);
    $('.edit-calendar-schedular').fullCalendar(
            'renderEvent', FCevent, true);
    form.closest('.ui-dialog-content').dialog('destroy');
    return false;

  });
  $('.view-calendar-schedule').fullCalendar({
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

    var model_id = $('.edit-calendar-schedular').attr('data-modelid');
    var fetchUrl = $('.edit-calendar-schedular').attr('data-fetchurl');
    var jsonSrc = {
      url : fetchUrl,
      type : 'POST',
      data : {
        model_id : model_id,
        _token  : $('meta[name="csrf-token"]').attr('content')
      }, 
      error : function () {alert ("There was an error fetching");}
    };
  //console.log("About to start full Calendar, the src obj:", jsonSrc);
  $('.edit-calendar-schedular').fullCalendar({
    editable: true,
    dayClick: createFCEventObject,
    eventClick: createEventEditDialog,
    eventSources: [ jsonSrc ],

    loading: function () {
      //console.log("LOAD event");
      //ajaxFetchEvents();
    }, //
    viewRender: function () {
      //console.log("ViewRender event");
      //ajaxFetchEvents();
    }, //
    eventAfterAllRender: function () {
      //console.log("eventAfterAllRender event");
      //ajaxFetchEvents();
    }, //
    widowResize: function () {
      //console.log("windowResize event");
      //ajaxFetchEvents();
    }, //
    //loading : function() {console.log("The Calendar is LOADING");},
    customButtons: {
      forceFetchButton: {
        text: 'Force Fetch',
        click: ajaxFetchEvents
      }, 
      myCustomButton: {
        text: 'Save Calendar',
        click: ajaxPostEvents
      }, 
      myRenderButton : {
        text : "Force Render",
        click : forceRender
      }
    },
    lazyFetching: false,
    header: {
      left: 'prev,next today myCustomButton',
      center: 'title, myRenderButton, forceFetchButton',
      right: 'month,agendaWeek,agendaDay'
    }
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


$(function () {

  $("#tabs").tabs({
    activate: function (event, ui) {
      $('#calendar').fullCalendar('render');
    },
    active: 1
  });
});
