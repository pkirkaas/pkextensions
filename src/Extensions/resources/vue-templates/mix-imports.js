/* 
 * To include the Extension Vue components
 * in webpack.mix.js, include this
 * and open the template in the editor.
 */

//My Drag n Drop Component:
//import PkDragndrop from './pk-dragndrop.vue';
//window.Vue.component('pk-dragndrop', require('./.vue'));
//Vue.component('pk-dragndrop',PkDragndrop);


/**
 * Extends Vue - whatever element has css class 'vueroot' will have a pkVue 
 * template appended/inserted to it, so don't need to specify a unique ID in
 * the page or template in the page  - happens automatically if the constructor
 * args contain 'appendTemplate' as a string of a vue template. 
 * If arg.placement ==  'after', vue is placed AFTER the element, else within
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
    super(arg);
    console.log("In pkVue constructor");
  }}


window.pkVue = pkVue;

window.Vue.component('pk-dragndrop', require('./pk-dragndrop.vue'));
