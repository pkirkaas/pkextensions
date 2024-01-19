'use strict';
/**Copyight (C) 2016 by Paul Kirkaas - All Rights Reserved */
// Mostly junk, maybe something interesting
//var Vue = require( 'vue');
import Vue from 'vue';
class PkVue extends Vue {

  constructor(arg){
    if (arg.appendtemplate) {
      var uid ='uid-'+elid(); // Make random unique ID
      var vueroot = "vueroot";
      if(arg.placement =='after')  { //Could be after the element, default is inside
        $(arg.el).after('<div class="'+uid+' '+vueroot+'">'+arg.appendtemplate+'</div>');
      } else {
        $(arg.el).append('<div class="'+uid+' '+vueroot+'">'+arg.appendtemplate+'</div>');
      }
      arg.el = '.'+uid+'.'+vueroot;
      delete arg.appendtemplate;
      delete arg.placement;
    }
    //If we want to use Ajax to initialize the Vue, just pass an array of keys
    //as one of the constructor args, the initial data function will just be an
    //object of those keys & no value
    // Make a data function that initializes the keys to {}; for ajax
    //Keys should be an array. Maybe refactor to give structure.
    var mixin = {};
    if (arg.keys) {
      mixin.data = {};
      for (var i=0 ; i<arg.keys.length ; i++) {
        mixin.data[arg.keys[i] ] = {};
      }
    }
      console.log("In construct, arg:",arg);
    //If a constructor key is 'ajax', it has the parameters for the 
    //AJAX call to initilaize the view data
      if (arg.ajax) { //Should have the arguments to initialize data
        var ajax = arg.ajax;
        var  init = function() {
          var self = this;
          console.log("In mounted init");
          var method = ajax.method || 'get';
          var url = ajax.url;
          var params = ajax.params || {};
          axios[method](url,{params:params})
            .then(resp=>{
              for (var i=0 ; i<arg.keys.length; i++) {
                self[arg.keys[i] ] = resp.data[arg.keys[i]];
              }
            })
            .catch(error=>{ console.log("Ajax Failed: URL", url,"params:", params, "result:", error);});
      };
      mixin.mounted = init;
      }
      if (!arg.mixin) {
        arg.mixin = [];
      }
      arg.mixin.push(mixin);
    super(arg);
    console.log("In pkVue constructor");
  }
} ;






Vue.buildInput = function(params) {
  //console.log("Paramas", params);
  var reginptypes = ['text','password','color','date','datetime-local', 'email',
    'month', 'number', 'range', 'search', 'tel', 'time', 'url', 'week'];
  var defcls = " rt-inp ";
  var name = params.name;
  var val = params.val;
  var placeholder = params.placeholder || '';
  var inpcls = params.inpcls + defcls ;
  var cmnatts = ' name="'+name+'" class="'+inpcls+'" placeholder="'+
          placeholder+'" '+ params.inpatts + ' ';
  var type = params.type || 'text';
  //console.log("Build Input: params:",params,"cmnatts:",cmnatts);
  if (reginptypes.indexOf(type) !== -1){ //It's a regular textish input type
    return '<input type="'+type+'" value="'+htmlEncode(val)+'" '+cmnatts+'/>';
  } else if (type === 'select') { // Need inpparams for select:
    //console.log("IN Bld Inp; Params:",params,'val',val);
    //allownull: default: true - if string, the string display for empty
    var allownull = params.allownull; //Trueish, falseish, or a string placeholder
    if (allownull === undefined) {
      allownull = true;
    }
    var options = params.options;
    //options: object keyed by value=>display
    var inp = '\n<select '+cmnatts + '>\n';
    var selected = "";
    if (allownull) {
      if (typeof allownull !== 'string') {
        allownull='';
      }
      if (!val) {
        selected = " selected ";
      }
      inp += '  <option value="" '+selected+'>'+allownull+'</options>\n';
      selected = '';
    }
    for (var key in options) {
      if (key === val) {
        selected = " selected ";
      }
      inp += '  <option value="'+key+'" '+selected+'>'+options[key]+'</options>\n';
      selected = '';
    }
    inp += "</select>\n";
    return inp;
  } else if (type === 'checkbox') { //Add hidden empty in case cleared
    var checkedval = params.inpparams.checkedval || "1";
    var inp = '<input type="hidden" name="'+name+'"/>\n';
    var checked = val===checkedval ? " checked " : "";
    inp += '<input type="checkbox" value="'+htmlEncode(checkedval)+'" '+checked+'/>\n';
    return inp;
  } else if (type === 'textarea') { 
    var inp = '\n<textarea '+cmnatts +'>'+val+'</textarea>\n';
  } else {
    throw "Unhandled input type ["+type+"]";
  }
};








window.PkVue = PkVue;
module.exports = PkVue;
