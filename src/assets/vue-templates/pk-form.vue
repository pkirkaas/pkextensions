<!--  A Basic form composable of components - based on Vue 2.6 Slots
    with custom v-atts directive
Usage:
Included as a component with a single prop object "params"
-->




<template>
  <form  method="POST" class="pk-v-form" :class="form.class" v-atts="form.formatts">
    <input type='hidden' name="token" v-atts="form.tokenatts" :value="csrf">
    <div class="pk-v-form-inner" v-atts="form.frameatts">
      <slot v-bind:params="slotparams">


      </slot>
    <button v-if="form.showbutton" v-atts="form.buttonatts" :type="form.buttontype || 'submit'" v-html='form.buttonlabel || "Submit"'></button>
    </div>
  </form>
</template>

<script>
//require('./vue-data-components.js');
export default {
  //props: ['form', 'formdata', 'componentdata', 'extraparams','batts'],
  /** Single prop: 'params' - with 2 sub-objects - form & slotparams
   * form: just for the form:
   *    class(string/object),
   *    formatts(object, formatts=>values),
   *    tokenatts, frameatts,
   *    showbutton(boolean - show a button on the form?)
   *    buttonatts(object - the button attributes)
   */
  props: {params: {
      type: Object,
      default: { form: {}, slotparams: {} },},
    },
  name: 'pk-form',
  mounted: function() {
    console.log("The props params:",this.params);
  },
  data: function() {
    return {
      csrf: $('meta[name="csrf-token"]').attr('content'),
    };
  },
  computed: {
    form: function() {
      console.log("Computed form: ",this.params.form);
      return this.params.form;
    },
    slotparams: function() {
      return this.params.slotparams;
    },
  },
};
</script>