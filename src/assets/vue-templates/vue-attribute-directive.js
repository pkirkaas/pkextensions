/**Copyight (C) 2016 by Paul Kirkaas - All Rights Reserved */
/* 
 * This creates a directive that allows you to specify
 * arbitrary attributes (including data-xxx & values in elements.
 * Use by just <tt>Vue.directive(require(....));</tt>
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

window.Vue.directive('atts',{
  bind: function (el, binding, vnode) {
    console.log("Binding in vue-att-dir.js");
    var value = binding.value;
    if ((typeof value !== 'object') ||  value === null) {
      return;
    }
    for (var att in value) {
      el.setAttribute(att, value[att]);
    }
  },
});


