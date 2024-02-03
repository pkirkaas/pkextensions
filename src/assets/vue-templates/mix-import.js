/**Copyight (C) 2016 by Paul Kirkaas - All Rights Reserved */
/* 
 * To include the Extension Vue components
 * in webpack.mix.js, include this
 * and open the template in the editor.
 */

//My Drag n Drop Component:
//import PkDragndrop from './pk-dragndrop.vue';
//window.Vue.component('pk-dragndrop', require('./.vue'));
//Vue.component('pk-dragndrop',PkDragndrop);

window.axios = require('axios');
//window.Vue = require('vue');
import Vue from 'vue';
window.Vue = Vue;
import Popper from 'popper.js';
window.Popper = Popper;
/**
 * Extends Vue - whatever element has css class 'vueroot' will have a pkVue 
 * template appended/inserted to it, so don't need to specify a unique ID in
 * the page or template in the page  - happens automatically if the constructor
 * arg contain 'appendTemplate' as a string of a vue template. 
 * If arg.placement ==  'after', vue is placed AFTER the element, else within
 */

/**
 *  Vue 3 doesn't like this. Will removing it break anything? 2024
 */
class pkVue extends Vue {constructor(arg){
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
    // Make a data function that initializes the keys to {}; for ajax
    //Keys should be an array. Maybe refactor to give structure.
    if (arg.keys) {
      var mixin = {};
      mixin.data = {};
      for (var i=0 ; i<arg.keys.length ; i++) {
        mixin.data[arg.keys[i] ] = {};
      }
      console.log("In construct, arg:",arg);
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
      if (!arg.mixin) {
        arg.mixin = [];
      }
      arg.mixin.push(mixin);
    }
    super(arg);
    console.log("In pkVue constructor");
  }
}} ;

window.pkVue = pkVue;


/** Use like: to initialize from AJAX
    var vg = new pkVue({
      el: '#vue-home',
      keys: ['text','arrs','anajax'],
      ajax: {url: '/ajax', params: {action: 'test'}},
});
*/

//window.Vue.component('pk-dragndrop', require('./pk-dragndrop.vue'));

//import PkDragndrop from './app/resources/vue-components/pk-dragndrop.vue';
//import PkShowimage from './app/resources/vue-components/pk-showimage.vue';
import PkDragndrop from './pk-dragndrop.vue';
import PkShowimage from './pk-showimage.vue';
pk-showimage.vue;
window.Vue.component('pk-dragndrop',PkDragndrop);
window.Vue.component('pk-showimage',PkShowimage);

// 2024 - try pasting buildImport ....



window.Vue.buildInput = function(params) {
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

