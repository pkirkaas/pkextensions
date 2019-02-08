<!--  A Basic form composable of components - based on Vue 2.6 Slots
    with custom v-atts directive
Usage:
Included as a component with a single prop object "params"
-->




<template>
  <form  method="POST" class="pk-v-form" :class="form.class" v-atts="form.formatts">
    <input type='hidden' name="token" v-atts="form.tokenatts" :value="csrf">
    <div class="pk-v-form-inner" v-atts="form.frameatts">
      <slot v-bind:formdata="formdata" v-bind:componentdata="componentdata" v-bind:extraparams="extraparams" v-bind:batts="batts" v-bind:dbatts="d">


    </slot>
    <button v-if="form.showbutton" v-atts="form.buttonatts" :type="form.buttontype || 'submit'" v-html='form.buttonlabel || "Submit"'></button>
    </div>
  </form>
</template>

<script>

export default {
  //props: ['form', 'formdata', 'componentdata', 'extraparams','batts'],
  props: {params: { form: {}, slotparams: {} },},
  name: 'pk-form',
  data: function() {
    return {
      form: {},
      csrf: $('meta[name="csrf-token"]').attr('content'),
    };
  },
  computed: {
    form: function() {
      return this.params.form;
    },
    slotparams: function() {
      return this.params.slotparams;
    },
  },
};
</script>