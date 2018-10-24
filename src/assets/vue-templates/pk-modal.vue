<!-- A generic modal container. Takes props: "modalparams" & "contentparams".
   "modalparams" are title, formatting, etc. 
   "contentparams" could be another comonent, or just raw HTML content to
    display.
  If "contentparams" is an object with the property "cname", it's a component
  & substituted in the "component :is="contentparams.cname", & content component
    :params="contentparams"
 -->
<template>
  <div class='modal-backdrop'>
    <div class='input-container'>
  <div class='modal-container input-container' id="input-container" 
       :class="modalparams.modalContainerCls">
    <div class="modal-title" :class="modalparams.modalTitleCls" v-html="modalparams.title"></div>
    <div class="modal-body" :class="modalparams.modalBodyCls" >


      <template v-if="contentIsComponent()">
      <component ref="content" :is="contentparams.cname" :params="contentparams" ></component>
      </template>
      <slot></slot>


    </div>
    <div class="button-row" :class="modalparams.buttonRowCls">
      <div v-if="modalparams.submit !== false" class="btn btn-primary mdl-btn submit-btn"
           :class="modalparams.submitBtnCls"
           @click="submit">{{modalparams.submitlbl || "Submit"}}</div>

      <div class="btn btn-secondary mdl-btn cancel-btn"
           :class="modalparams.cancelBtnCls" 
           @click="$emit('close')">{{modalparams.cancellbl || "Cancel"}}</div>
    </div>
  </div>
  </div>
  </div>

</template>

<script>

/*
  */
export default {
    name: 'pk-modal',
    data: function(){
      return {
        reloadrefs: [],
      };
    },
    //modalparams: url, data (added to formdata), formatting, title
    //contentparams - probably an inner comonent, conentparams={cname,cdata}
    props: ['contentparams', 'modalparams'],
    mounted: function() {
      if (this.modalparams.reloadrefs) {
        var reloadrefs = this.modalparams.reloadrefs;
        if (!Array.isArray(reloadrefs)) {
          realoadrefs = [reloadrefs]; 
        }
        this.reloadrefs = reloadrefs;
        if (this.contentparams.reloadrefs) {
          if (!Array.isArray(this.contentparams.reloadrefs)) {
            this.contentparams.realoadrefs = [this.contentparams.reloadrefs]; 
          }
          this.contentparams.reloadrefs.concat(reloadrefs);
        }
      }
      console.log("PkModal, params:", this.modalparams, 'content',this.contentparams);
    },
    methods: {
      /** Will combine data directly provided in modalparams.data,
       *  with any possible data from the form
       * @param {type} event
       * @returns {undefined}
       */
      contentIsComponent() {
        if ((typeof this.contentparams === 'object') && this.contentparams.cname) {
          return true;
        }
      },
      submit: function(event) {
        console.log("Submit from Modal; loadedrefs:",this.reloadrefs);
        if (this.$refs.content.submit &&(typeof this.$refs.content.submit === 'function')) {
          console.log("But the actual submit will be done by the subcomponent");
          this.$refs.content.submit(event);
          $(":input.jq-wipe").val('');
          this.$parent.showModal=false;
          this.reset();
        } else {
          console.error("We should be processing on our onwe as a test!");
                  
          console.log("Submitting from modal");
          var fd = new FormData();
          var url = this.modalparams.url;
          $('#input-container').find(":input").each(function(idx, el) {
              var $el = $(el);
            console.log("Iterating: Name",$el.attr('name'),"Val:",$el.val());
              fd.append($el.attr('name'),$el.val());
            }); 
          //Now add direct data, if any
          if (this.modalparams.data && (typeof this.modalparams.data === 'object')) {
            for (var key in this.modalparams.data) {
              fd.append(key,this.modalparams.data[key]);
            }
          }
          for(var pair of fd.entries()) {
            console.log(pair[0]+ ', '+ pair[1]); 
          }
          console.log('url',url);
          /** // Debugging
          */
          axios.post(url,fd).
            then(response=>{
              console.log("Success: Response:",response);
      
              //this.$emit('submitmsg',"Refresh");
              //this.$parent.$emit('submitmsg',"Refresh");
              //this.$parent.$parent.$emit('submitmsg',"Refresh");
              if (this.reloadrefs) {
                this.reloadrefs.forEach(function(el) {
                  if (el.initData) {
                    el.initData();
                  }
                });
              }
            }).
            catch(error=>{
              console.error("Error:",error.response);
            });
          $(":input.jq-wipe").val('');
          this.$parent.showModal=false;
          this.reset();
        }
      },
      reset: function() {
        var vm = this;
        vm.drawComponent = false;
        Vue.nextTick(function() {
          vm.drawComponent = true;
        });
      },

      cancel: function(event) {
        this.$parent.showModal=false;
        this.$parent.$emit('cancel',"Stop");
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
        this.reset();
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
    top: 4rem;
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
