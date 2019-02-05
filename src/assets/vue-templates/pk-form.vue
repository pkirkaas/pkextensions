<!--  A Basic form composable of components -
    with say slots and custom directives -->
<template>
  <form  method="POST" class="pk-v-form" :class="form.class" v-atts="form.formatts">
    <input type='hidden' name="token" v-atts="form.tokenatts" :value="csrf">
    <div class="pk-v-form-inner" v-atts="form.frameatts">
    <slot v-bind:formdata ="formdata" v-bind:componentdata="componentdata" v-bind:extraparams="extraparams">


    </slot>
    <button v-if="form.showbutton" v-atts="form.buttonatts" :type="form.buttontype || 'submit'" v-html='form.buttonlabel || "Submit"'></button>
    </div>
  </form>
</template>

<script>

export default {
  props: ['form', 'formdata', 'componentdata', 'extraparams'],
  name: 'pk-form',
  data: function() {
    return {
      csrf: $('meta[name="csrf-token"]').attr('content'),
    
    };
  },
};
</script>