<template>
  <div class='modal-backdrop'>
  <div class='modal-container input-container' id="input-container"
       :class="params.modalContainerCls" @click='stopHere'>
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
           @click="$emit('close')">{{params.cancellbl || "Cancel"}}</div>
    </div>
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
    props: ['params','showModel'],
    mounted: function() {
      //console.log("PkModal, params:", this.params);
    },
    methods: {
      stopHere: function(event) {
        console.log("Still hopeing to stop prop");
      },
      submit: function(event) {
        console.log("Submitting");
        var fd = new FormData();
        var url = this.params.url;
        $('#input-container').find(":input").each(function(idx, el) {
            var $el = $(el);
          console.log("Iterating: Name",$el.attr('name'),"Val:",$el.val());
            fd.append($el.attr('name'),$el.val());
          }); 
        for(var pair of fd.entries()) {
          console.log(pair[0]+ ', '+ pair[1]); 
        }
        axios.post(url,fd).
          then(response=>{
            console.log("Success: Response:",response);
    
            this.$parent.$emit('submitmsg',"Refresh");
          }).
          catch(error=>{
            console.error("Error:",error.response);
          });
        $(":input.jq-wipe").val('');
        this.$parent.showModal=false;
        
      },

      cancel: function(event) {
        /*
        $(":input.jq-wipe").val('');
        this.$emit('cancel',"Stop");
        this.$parent.$emit('cancel',"Stop");
        this.params.message = ssage = '';
        this.$parent.showModal=false;
        this.$emit('custome',"hellow");
        this.$parent.$emit('custome',"Hi daddy");
        this.$destroy();
        this.$emit('close');
        this.$parent.$emit('close');
        */
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
    min-height: 150px;
    min-width: 500px;
    display: flex;
    padding: 1em;
    flex-direction: column;
  }
  .modal-container {
    z-index: 1000;
    background-color: #eee;
    color: #444;
    font-size: larger;

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
    .modal-backdrop {
    position: fixed;
    top: 0;
    bottom: 0;
    left: 0;
    right: 0;
    background-color: rgba(0, 0, 0, 0.3);
    display: flex;
    justify-content: center;
    align-items: center;
  }
</style>
