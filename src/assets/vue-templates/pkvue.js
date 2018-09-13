'use strict';
var Vue = require( 'vue');
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

window.PkVue = PkVue;
module.exports = PkVue;
