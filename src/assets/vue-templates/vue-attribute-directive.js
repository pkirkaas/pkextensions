/* 
 * This creates a directive that allows you to specify
 * arbitrary attributes (including data-xxx & values in elements.
 * Use by just <tt>Vue.directive(require(....));</tt>
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

Vue.directive('atts',{
  bind: function (el, binding, vnode) {
    var value = binding.value;
    if ((typeof value !== 'object') ||  value === null) {
      return;
    }
    for (var att in value) {
      el.setAttribute(att, value[att]);
    }
  },
});


