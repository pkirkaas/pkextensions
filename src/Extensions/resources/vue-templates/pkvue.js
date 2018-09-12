'use strict';
var Vue = require( 'vue');
class PkVue extends window.Vue {constructor(arg){

  /*
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
      var mixin = {}
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
    */
    super(arg);
    console.log("In pkVue constructor");
  }
} ;

window.PkVue = PkVue;
module.exports = PkVue;
