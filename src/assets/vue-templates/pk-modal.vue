<template>
  <div class='modal-container input-container' id="input-container"
       :class="params.modalContainerCls">
    <div class="modal-title" :class="params.modalTitleCls" v-html="params.modalTitle"></div>
    <div class="modal-body" :class="params.modalBodyCls" >
      <slot></slot>
    </div>
    <div class="button-row" :class="params.buttonRowCls">
      <div class="btn btn-primary mdl-btn submit-btn"
           :class="params.submitBtnCls"
           @click="submit">{{params.submitlbl || "Submit"}}</div>

      <div class="btn btn-secondary mdl-btn cancel-btn"
           :class="params.cancelBtnCls" 
           @click.stop="cancel">{{params.cancellbl || "Cancel"}}</div>
    </div>
  </div>

</template>

<script>

export default {
    name: 'pk-modal',
    data: function(){
      return {};
    },
    //params: url, data (added to formdata),
    props: ['params'],
    mounted: function() {
      console.log("PkModal, params:", this.params);
    },
    methods: {
      submit: function(event) {
        var fd = new FormData();
        $('#input-container').filter(":input").each(function(idx, el) {
            var $el = $(el);
            fd.append($el.attr('name'),$el.val());
          }); 
        for(var pair of fd.entries()) {
          console.log(pair[0]+ ', '+ pair[1]); 
        }
      },
      cancel: function(event) {
        this.$parent.showModal=false;
        this.$emit('custome',"hellow");
        this.$parent.$emit('custome',"Hi daddy");
        this.$destroy();
        this.$emit('close');
        this.$parent.$emit('close');
        console.log("In Modal, Trying to close");
      },
    },
  }
</script>


<style>
  .modal-title {
    display: inline;
    width: 100%;
    padding: .5em;
    text-align: center;
    color: white;
    background-color: #00a;
    font-weight: 600;
  }
.button-row {
  display: inline-flex;
  justify-content: space-between;
  padding: .5em 2em;
  }
  .modal-body {
    flex-grow: 1;
    min-height: 50px;
    min-width: 200px;
    display: flex;
    padding: 1em;
  }
  .modal-container {
    z-index: 1000;
    background-color: #bbb;

    position: fixed;
    top: 8em;
    margin-right: 0;
    margin-left: 0;
    left: 50%;
    transform: translate(-50%,0); 
    display: flex;
    flex-direction: column;
    border: solid #a88 1px;
    border-radius: .5em;

  }
</style>
