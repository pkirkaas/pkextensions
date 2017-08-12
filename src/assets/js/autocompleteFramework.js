/**
 * This file generalizes a common jQuery/jQuery UI autocomplete use case, so specific
 * arguments are used to customize the behavoir.
 *
 * General Description:
 * An autocomplete function that attaches to a text input. Makes an AJAX call to
 * a backend with parameters, typically a DB search. The AJAX call returns a
 * JSON array of objects with keys "id" & "value", after a number of characters
 * are entered. A selectable list is displayed if there are matches.
 * If the user selects from the list, the text box is populated with the value, and
 * the hidden ID (todo: make a data-id property) is set.
 * IF there is no match and the user leaves the text box, the user is prompted to
 * ask if they want to create this new entry. If the user confirms, the entry is
 * created by another AJAX call, which returns the ID of the newly created entity,
 * which is then put into the id field.
 *
 * Will attempt to set both a hidden input field with the selector $(inputSelector+"_id"),
 * as well as set the attribute $(inputSelector).attr('data-id', id);
 * TODO: 
 *
 * Paul Kirkaas, based on code developed for personal projects long before joining
 * Disney.
 * 4/2/2014 7:33:58 PM, v. 1.0
 */

/**
 * Initializes a text input field for autocomplete and optional creation of
 * a new entry. Argument is a param object:
 * @param String param.inputSelector - the selector of the text input field
 * @param String param.ajaxUrl -- the base URL for the ajax calls, without
 *   the function to be called, or the arguments.
 * @param Object param.listSearchParams -- object containing the specific api call for the list search 
 *   function, as well as parameters required for the list search function
 * @param Object param.itemSearchParams -- object containing the specific API call to return a single
 *   ID that matches the value, as well as the "value" parameter to match.
 * @param Object param.createItemParams -- object containing the API function to call to create a new
 *   entity ('apicall'), as well as additonal values (region_id, whatever). Expected return is the newly created item ID.
 * @param Object param.minLength -- Optional: integer length of minimum character input. Default: 4
 * @param Object param.parentSelector -- Optional: String: If present, accounts for multiple autocomplete
 *   inputs with same class name on the page, by finding a sibling of this parent div.
 *   
 * @param Object param.name -- Optional: String: The user friendly name of the item
 *   
 *   <p>Sample PHP Code:
 *   
# Returns json for ajax call for auto-complete of Test table data
# Uses request parameters 
function api_test_ac() {
  $term = $_REQUEST['term'];
  $db = getDb();
  $res = $db->query("SELECT `id`, `value` FROM `test_table`
     WHERE `value` LIKE ".$db->quote($term.'%')."
     ORDER BY `value`")->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode($res);
}

 */

function initializeAC(param) {
//function initializeAC(inputSelector,ajaxUrl, listSearchParams, itemSearchParams, createItemParams) {
  var name = "Item";
  if (param.name) name = param.name;
  var minLength = 4;
  if (param.minLength) minLength = param.minLength;
  var parentSelector = param.parentSelector;
  var inputSelector = param.inputSelector;
  var createItemParams = param.createItemParams;
  var ajaxUrl = param.ajaxUrl;

  $(param.inputSelector).autocomplete({
    minLength: minLength,
    html: true,
    select: function(event, ui) {
      //On select, do a "blur" (lose focus) and trigger "change" event
      event.preventDefault();
      $(this).val(ui.item.label);
      $(this).attr('data-id',ui.item.value);
      this.item = ui.item;
      console.log(param.inputSelector + " AC Select, UI:",ui);
      //$(this).trigger('blur');
    },

    change: function(event, ui) {
      console.log(param.inputSelector +" AC CHANGE (before set to this.item)!, UI:",ui);
      if ((ui.item == undefined) || (!ui.item)) {
        ui.item = this.item;
        this.item = undefined;
      }
      var value = $(this).val();
      if (value && ((ui.item == undefined) || !ui.item)) { //Doesn't exist, create
        console.log("value is real, ui.item is undefined");
        //A value was entered manually, not selected, so check if it already exists in the DB,
        //If so, populate the id field, if not, ask to create it.
        //ac_lastXhr_em = $.getJSON("magiclivingajax.php",{'apicall':'getbrands-ac', brand:brand},
        //ac_lastXhr_em = $.getJSON(param.ajaxUrl,{apicall:param.itemSearchParams.apicall, value:value},
        ac_lastXhr_em = $.getJSON(param.ajaxUrl, {term:value},
         function( data, status, xhr ) {
           //Expect data to be an integer ID of found item, else 0/''/false
          console.log("data/id in change for "+param.inputSelector + " is: ",data);
          if(data) { //Set both hidden input id and data_id for this text box.
            if (parentSelector) {
              //$(this).parents(".brand-input-set-div:first").find(".brand_id_input").val(data);
              $(this).parents(parentSelector+":first").find(inputSelector+"_id").val(data);
            } else {
              $(inputSelector+"_id").val(data);
            }
            $(inputSelector).attr('data-id',data);
          } else {
             //thisCreateConfirmDialog = createConfirmDialog(name,value);
             createItemParams.value = value;
             thisCreateConfirmDialog = createConfirmDialog(
                 {name:name,
                  me:this,
                  ajaxUrl:ajaxUrl,
                  params:createItemParams,
                  inputSelector:inputSelector
                 }
               );
             thisCreateConfirmDialog.ctl = this;
             //thisCreateConfirmDialog.brand = brand;
             //thisCreateConfirmDialog.type = $(this).parents(".user-wrapper:first").find("#account-level-select").val();
             //gtype = $(".account-level-select").val();
             console.log("thisCreateConfirmDialog.brand: ",thisCreateConfirmDialog.brand);
             thisCreateConfirmDialog.html("The "+name +" [<b>"+value+'</b>] does not exist. Do you want to create this new '+name+'?');
             thisCreateConfirmDialog.dialog('open');
             return;
          }
        });
      } else { //Brand Exists, select and set hidden input ID, but first confirm:
        if (ui.item === undefined || !ui.item) {
          console.log("??? Couldn't find "+name+" ID. UI:",ui);
          $(this).val('');


          if (parentSelector) {
            $(this).parents(parentSelector+":first").find(inputSelector+"_id").val('');
          } else {
            $(inputSelector+"_id").val('');
          }
          $(inputSelector).attr('data-id','');

          return false;
        } else { //We have an ID

          $(this).parents(".brand-input-set-div:first").find(".brand_id_input").val(ui.item.id);
          if (parentSelector) {
            $(this).parents(parentSelector+":first").find(inputSelector+"_id").val(ui.item.id);
          } else {
            $(inputSelector+"_id").val(ui.item.id);
          }
          $(inputSelector).attr('data-id',ui.item.id);
        }
      }
    },

    source: function(request, response) {
       console.log(param.inputSelector +" AC Source; request:",request);
       //request.apicall=param.listSearchParams.apicall;
       //request.apicall=param.listSearchParams.apicall;
       console.log("BrandAC Source; request (after set api-call):",request);
       ac_lastXhr_em = $.getJSON(param.ajaxUrl, request, function( data, status, xhr ) {
         if ( xhr === ac_lastXhr_em ) {
           response( data );
           console.log(param.inputSelector + " AC Source; response data:",data);
         }
       }
      );
    }






  });
}

/**
 * Creates a confirmation dialog for param. New created each time. 
 * Parameters are an object "param" of key/value pairs:
 * #@param param.value String: The name to be created
 * @param String param.ajaxUrl -- the base URL for the ajax calls, without
 *   the function to be called, or the arguments.
 * @param Object param.params -- containing specific api (as 'apicall') and value (as 'value')
 *   to be created. 
 * @param param.inputSelector: Optional -- if present, use as selector, otherwise, use 'this'
 * @param param.parentSelector: Optional -- if present, use as parent selector to set sibling hidden field
 * @param param.name: String, optional, the name of the item to be created.
 * @return The dialog object
 */
//function createConfirmDialog(type, value) {
function createConfirmDialog(param) {
  var name='Item';
  if (param.name) name = param.name;
  var ajaxUrl = param.ajaxUrl;
  var me = param.me;
  var inputSelector = param.inputSelector;
  var parentSelector = param.parentSelector;
  var createItemParams = param.createItemParams;
  var args = param.params;
  //var value = param.value;
  //var apicall = param.apicall;
  /*
type, value) {
  switch(type) {
    case('brand'):
      var typeStr = 'Brand';
      var 
      var apiCall = 'createbrand';
      var args = {apicall:'createbrand',brand:value};
      break;
    default:
      return false;
  }
    */
  console.log("In create dialog; me is:",me);
  var dlg = $('<div></div>')
    .attr('title',"Create New "+name+"?")
    .dialog({
      resizable: false,
      modal: true,
      autoOpen: false,


      buttons: {
        //"Create new "+typeStr: function() {
        "Create new Entry": function() {

          $(this).dialog('close');
          //var args = {ssoid:this.ssoid, type:this.type};
          //var args = {ssoid:gssoid, type:gtype};
          console.log("Args in create "+name, param.params);
          ac_lastXhr_em = $.getJSON(ajaxUrl, args , function( data, status, xhr ) {
            console.log("Return ID for new record: ", data);
            if (inputSelector) {
              $(inputSelector).attr('data-id',data);
              if (parentSelector) $(me).parents(parentSelector+":first").find(inputSelector+"_id").val(data);
            }
            else  $(me).attr('data-id',data);


            //$("."+type+"_id_input").val(data);
            //console.log("email-id ctl value: ",$(".email-id").val());
          });
        },
        "Cancel": function() {
          $(this).dialog('close');
        }
      }
    });
    return dlg;
}


