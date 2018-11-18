<!-- A generic modal container. Takes props: "modalparams" & "contentparams".
   "modalparams" are title, formatting, etc. 
   "contentparams" could be another comonent, or just raw HTML content to
    display.
  If "contentparams" is an object with the property "cname", it's a component
  & substituted in the "component :is="contentparams.cname", & content component
    :params="contentparams"
So the 'content' component MUST accept props as "params" - which will be an object
of potentially 3 keys:
props: {params: {cname: content component name
                 cdata: { Possible data for the content form }
                 cparams: {possible extra settings, classes, labels, etc
}
 -->
<template>
 <transition name="modal">
  <div class='modal-backdrop'>
    <div class='input-container'>
  <div class='modal-container input-container' id="input-container" 
       :class="modalparams.modalContainerCls">
    <div class="modal-title" :class="modalparams.modalTitleCls" v-html="modalparams.title"></div>
    <div class="modal-body" :class="modalparams.modalBodyCls"
         :style="modalparams.modalBodyStyle">


      <template v-if="contentIsComponent()">
        <component ref="content" :is="contentparams.cname" :params="contentparams" ></component>
      </template>
      <template v-else-if="contentIsHtml()" v-html="contentparams.html">
        <div v-html="contentparams.html"></div>
      </template>
      
      <slot></slot>


    </div>
    <div class="button-row" :class="modalparams.buttonRowCls">
      <div v-if="modalparams.submit !== false" class="btn btn-primary mdl-btn submit-btn"
           :class="modalparams.submitBtnCls"
           @click="submit">{{modalparams.submitlbl || "Submit"}}</div>

      <div class="btn btn-secondary mdl-btn cancel-btn"
           :class="modalparams.cancelBtnCls" 
           @click="close()">{{modalparams.cancellbl || "Cancel"}}</div>
    </div>
  </div>
  </div>
  </div>

 </transition>
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
/**
 * PARAMETERS:
  modalparams: url, data (added to formdata), formatting, title
  reloadrefs: an instance or array of component references that should
   be called with "ref.initData()" - to reload the contents of the
   ref components after the update from this submission
  contentparams - probably an inner component, contentparams={cname,cdata}
    but could be just the HTML for a form/input
    submit URL / Action: Multiple possiblitities. If the "contentparams"
    has a component with a "submit" method, just call that when the submit
    button is pressed. If "contentparams" OR "modalparams" has either
    "url" or "submiturl", get all input components from within the modal
    and submit them to the URL
*/
    props: ['contentparams', 'modalparams',  ],

    mounted: function() {
      this.setReloadRefs();

      //console.log("PkModal, params:", this.modalparams, 'content',this.contentparams);
    },
    methods: {
      /** Will combine data directly provided in modalparams.data,
       *  with any possible data from the form
       * @param {type} event
       * @returns {undefined}
       */
      setReloadRefs: function(reloadrefs) {
        var lrlr = [];
        
        if (reloadrefs) {
            lrlr = lrlr.concat(reloadrefs);
        } 
        if (this.modalparams.reloadrefs) {
          lrlr = lrlr.concat(this.modalparams.reloadrefs);
        }
        if (this.contentparams.reloadrefs) {
          lrlr = lrlr.concat(this.contentparams.reloadrefs);
        }
        if (this.reloadrefs) {
          lrlr = lrlr.concat(this.reloadrefs);
        }
        this.reloadrefs = uniqArr(lrlr);
        //console.log("PkModal-ReloadRefs:", this.reloadrefs);
        /*
    */
      },
      contentIsComponent() {
        if ((typeof this.contentparams === 'object') && this.contentparams.cname) {
          return true;
        }
      },
      contentIsHtml() {
        if ((typeof this.contentparams === 'object') && this.contentparams.html) {
          return true;
        }
      },
      submit: function(event) {
   
        //console.log("Submit from Modal; loadedrefs:",this.reloadrefs);
        if (this.$refs && this.$refs.content && this.$refs.content.submit
                &&(typeof this.$refs.content.submit === 'function')) {
          //console.log("But the actual submit will be done by the subcomponent");
          this.$refs.content.submit(event);
          $(":input.jq-wipe").val('');
          this.$parent.showModal=false;
          this.reset();
        } else {
          this.setReloadRefs();
          
          //console.error("We should be processing from 'content' as a test!");
          //console.log("Submitting from modal");
          var fd = new FormData();
          if (this.$refs && this.$refs.content) {
            var contenturl = this.$refs.content.url || this.$refs.content.submiturl;
          } else {
            var contenturl = false;
          }
          var submiturl = this.modalparams.url || this.modalparams.submiturl 
               || contenturl || '/ajax';
          $('#input-container').find(":input").each(function(idx, el) {
              var $el = $(el);
            //console.log("Iterating: Name",$el.attr('name'),"Val:",$el.val());
              fd.append($el.attr('name'),$el.val());
            }); 
          //Now add direct data, if any
          if (this.modalparams.data && (typeof this.modalparams.data === 'object')) {
            for (var key in this.modalparams.data) {
              fd.append(key,this.modalparams.data[key]);
            }
          }
          for(var pair of fd.entries()) {
            //console.log(pair[0]+ ', '+ pair[1]); 
          }
          //console.log('submiturl in pk-modal submit:',submiturl);
          /** // Debugging
          */
          axios.post(submiturl,fd).
            then(response=>{
              this.setReloadRefs();
              //console.log("Success: Response:",response, "RelRefs:",this.reloadrefs);
      
              //this.$emit('submitmsg',"Refresh");
              //this.$parent.$emit('submitmsg',"Refresh");
              //this.$parent.$parent.$emit('submitmsg',"Refresh");
              if (this.reloadrefs) {
                this.reloadrefs.forEach(function(el) {
                  if (el && el.initData) {
                    el.initData();
                  }
                });
              }
            }).
            catch(error=>{
              console.error("Error:",error,error.response);
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

      close: function(event) {
        return this.cancel(event);
      },
      cancel: function(event) {
        this.reset();
        this.$parent.showModal=false;
        this.$parent.$emit('cancel',"Stop");
        this.$emit('close')
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
        //console.log("In Modal, Trying to close");
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
    top: 6rem;
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
