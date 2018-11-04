/* 
 * Responsinve Vue data (label/value) components - both for input & display
 * Compose to make forms
 */

/*
//Experiment - an input container that loads & can save & show updates
window.Vue.component('pk-input-container',{
  name: 'pk-input-container',
  temlpate: `
  <div class='pk-input-container' :class="params.containerclass">
  <div class='input-wrapper' :class="params.inputwrapperclass">
  <div class='input' v-for="(control,idx) in params.controls" :class="control.class"
    <component is:"control.component" 
  </div>
  </div>
  </div>
`,
  props: ['params'],
});
*/


/** pk-modal-wrapper - creates a button that opens a modal when clicked.
 * Wraps both the button & modal - 
 *  the modal is a single file component pk-modal.vue
 * Full Params: 
 *  takes 1 prop: "params", containing 3  objects - 
 *  "btnparams" (for the button itself - title, appearance)
 * "modalparams" (For the modal box - size, title, submit url etc)
 * 'contentparams' - what's inside the modal. 
 *    The Content of the modal - generally containing inputs & submit URL,
 *    but could be HTML or a component, cname, w. cdata. If a component,
 *    if has a method "submit", that is called when the modal submit button
 *    is clicked, else look for url/submiturl.
 *    contentparams: {
 *      cname: component name,
 *      cdata: component data,
 *      html: simple HTML inputs
*      }
 * Simplified Params (w. defaults) - builds full param set (content, modal, btn)
 * { cname/cdata/html -> added to contentparams
 *   label: Button Label - added to 'btnparams'
 *   title: Modal Title - added to 'modalparams'
 *   
 *   url/submiturl: added to 'modalparams' if present - else, default to '/ajax'
 *   Then need to add 'action' param & use in AjaxController/index
 *   
 * 
 * 
Example all-in-one post-button.phtml:

<div id="vue-post-btn">
  <pk-modal-wrapper ref="modal_wrapper" :params="postparams"></pk-modal-wrapper>
</div>
<script>
Vue.component('post_form',{
  name:'post_form',
  template: `
<div>
  <input type='hidden' name='action' value='submitpost' />
  <div class="form-header" >What do you want to say?</div>
  <div class="post-title border" >
    <div class="pk-lbl">Title:</div>
    <input class="pk-inp" type="text" name="title" />
  </div>
  <div class="post-body">
    <textarea class="pk-inp" name="body"></textarea>
  </div>
</div>
  `,

});
// Note this is an independant Vue instance - should be OUTSIDE of other
// Vue instances
var post_btn = new Vue({
  el: "#vue-post-btn",
  data: {
    postparams: {
      label:"New Post",
      title:"Submit new post",
      cname:"post_form",
    },
  },
});

//// Alternatively, instead of cname, just the raw HTML - no 'post_form' comp.

  data: {
    postparams: {
      label:"New Post",
      title:"Submit new post",
      //submiturl: "/ajax",
      //cname:"post_form",
      html: `
<div>
  <input type='hidden' name='action' value='submitpost' />
  <div class="form-header" >What do you want to say?</div>
  <div class="post-title border" >
    <div class="pk-lbl">Title:</div>
    <input class="pk-inp" type="text" name="title" />
  </div>
  <div class="post-body">
    <textarea class="pk-inp" name="body"></textarea>
  </div>
</div>
  `,
    },
  },
*/

/** If ALSO want to provide component refs to refresh after update with this
 * post/submission, additionally have to add:
//To post_button or other form that changes data:
Note the Vue instance name for the pop-up form is "post_btn"
data: {
  postparams: {
    reloadrefs: [],
    label: ..
  }
},
methods: { // (But this is also in the mixin reloadRefsMixin)
  setReloadRefs: function (reloadrefs) {
    if (reloadrefs) {
      if (this.$refs && this.$refs.modal_wrapper
            && this.$refs.modal_wrapper.setReloadRefs) {
        this.$refs.modal_wrapper.setReloadRefs(reloadrefs);
      }
      this.postparams.reloadrefs = this.postparams.reloadrefs.concat(reloadrefs);
    }
  }
}

// Then, your main Vue instance on the page has to call the Vue instance setReloadRefs
// method with the components that want to be refreshed when data changes. 

  mounted: function() {
    this.registerRefresh();
  },
  methods: {
    registerRefresh: function() {
      post_btn.setReloadRefs(this.$refs.posts_component);
    },
  },

//
Finally, your posts-component has to implement method initData(), & use it for 
initial load (in mounted), & refresh being called by post_btn:
  mounted: function() {
    this.initData();
  },
  methods: {
    initData: function() {
      axios.post(url,data)....

... And when including posts-component, specify the reference:
  <posts-component ref="posts_component" :profile_id="profile_id"></posts-component>
 */
window.Vue.component('pk-modal-wrapper',{
  name: 'pk-modal-wrapper',
  template:`
  <div class='inline fs-1'>
    <pk-modal-btn :btnparams="btnparams"
       @click="openModal(event)"
       class="btn primary-btn" :class="btnparams.btnCls">
    </pk-modal-btn>


    <pk-modal ref="pk_modal" v-if="showModal" @close="showModal=false"
      :modalparams="modalparams" :contentparams="contentparams" :showModal="showModal" >
    </pk-modal>
  </div>
  `,
  //props:["contentparams","modalparams","btnparams"],
  props:['params'],
  methods : {
    openModal: function(event) {
      console.log("In wrapper, clicked on openModal w. event:",event);
      this.showModal = true;
    },
    callOwner: function() {
      console.log ("Wrapper got called when clicked on "+who);
    },
    setReloadRefs: function (reloadrefs) {
      console.log("In Modal_Wrapper setRR:",reloadrefs);
      if (reloadrefs) {
        if (this.$refs && this.$refs.pk_modal
              && this.$refs.pk_modal.setReloadRefs) {
          this.$refs.pk_modal.setReloadRefs(reloadrefs);
        }
        this.modalparams.reloadrefs=reloadrefs;
        this.reloadrefs=this.reloadrefs.concat(reloadrefs);
      }
      return this.reloadrefs;
    }
  },
  data: function() {
    return {
      showModal: false,
      contentparams: {},
      modalparams: {},
      btnparams: {},
      reloadrefs: [],
    };
  },
  mounted: function() {
    console.log("Modal-Wrapper mounted w. params:", this.params);
    var contentparams =  this.params.contentparams;
    var modalparams = this.params.modalparams;
    var btnparams =   this.params.btnparams;
    if (this.params.reloadrefs) {
      this.reloadrefs = this.reloadrefs.concat(this.params.reloadrefs);
    }

    if (!contentparams) {
      contentparams = {
        cname: this.params.cname, //Component name
        cdata: this.params.cdata, //Component data
        html: this.params.html, // OR - Just HTML
      };
    }
    this.contentparams = contentparams;
    if (!modalparams) {
      modalparams = {
        submiturl: this.params.submiturl || this.params.url,
        title: this.params.title || "Update",
        reloadrefs: this.params.reloadrefs,
      };
    }
    if (!btnparams) {
      btnparams = {
        label: this.params.label || "Submit",
      };
    }
    this.modalparams=modalparams;
    this.btnparams =  btnparams;
    console.log("After mounted. this.contentparams:", this.contentparams, "this.modalparams:",
      this.modalparams,"this.btnparams:", this.btnparams);
  },
});


/** Pops up a pk-modal component - DON'T USE DIRECTLY - USE pk-modal-wrapper! */

//the button - and send param / key to know which modal to open... unless
//the modal is generic, & the button passes everything to it?
//Create 3rd component that wraps both & provides data
  //<div style="z-index: 2000;" @click="doshowModal" class="btn btn-primary btn-pop m-5 p-f fs-8"
window.Vue.component('pk-modal-btn',{
  template: `
  <div style="z-index: 2000;" @click="onClick" class="inline btn btn-primary btn-pop m-2 p-f fs-6"
   :class="btnparams.popBtnCls"> {{btnparams.label}} </div>
  `,
   props: ['btnparams'],
  mounted: function() {
    console.log("PkModalBtn, btnparams",this.btnparams);
   },
   methods: {
    onClick: function(event) {
      console.log("modal-btn got clicked, event:",event);
      this.$parent.showModal = true;
    },
    callOwner: function() {
      console.log ("BUTTON got called when clicked on "+who);
    },
    /*
    doshowModal: function() {
      console.log("Ath least I cought the click");
      this.$parent.doshowModal();
    }
    */
  }
});


/** Could be a label, or data field - if data-field, just display or input 
 *@params: 
 *  type: label, datum
 *  value: 
 *  itmcls: CSS class
 *  width: opt - if int/intish, #px, if string, used as is
 *  map: opt - val=>display - if input, select opts, 
 *  input: what kind & how to build it -
 *    if just string, assume type is 'text' & string is 'name' 
 *    otherwise, object with
 *      type (text, select, checkbox, etc)
 *      name
 *    & paramaters necessary
 * */
window.Vue.component('data-item',{
  template: `
    <div :class="itmcls" :style="style" v-html="content"></div>
  `,
  props: ['params'],
  computed: {
    type: function() {return this.params.type;},
    style: function() {if (this.params.width) {
      var style = this.params.style;
      if (is_intish(this.params.width)) {
        var width=this.params.width+'px ';
      }
      else if (typeof params.width === 'string')
        var width = params.width;
      }
      if (typeof width === 'string') {
        var stylewidth = ' width: '+width;
      }
      return style + stylewidth;
    },
    itmcls: function() {
      if (this.params.type === 'label') {
        return this.params.itmcls + " rt-lblcls ";
      } else {
        return this.params.itmcls + " rt-fldcls ";
      }
    },
    content: function() {
      //console.log("In Content - params:",this.params);
      var input = this.params.input;
      var name = this.params.name;
      var value = this.params.value || this.params.val;
      var map = this.params.map;
      if ( this.params.type === 'label') {
        return value;
      }
      if (!input) {
         if ( map) {
            return map[value];
          } else {
            return value;
          }
      } else { // It is an input
        if (typeof input === 'string') {//Just text inp, inp is name
          input = {
            type: input,
            name: name,
            val: value,
            options: map,
          }
        } else if (typeof input === 'object') {// Must be object to have enough info
          input.val = input.val || input.value || value;
          input.options = map;
          console.log("In the component, map:", map);
        } else {
          throw "Input invalid type";
        }
        return Vue.buildInput(Object.assign({},this.params,input));
      }
    }
  },
});

Vue.component('data-label-pair', {
  name: 'data-label-pair',
  template: `
    <div :class="lblcls" :style="lblstyle" v-html="label"></div>
    <div :class="fldcls" :style="fldstyle" v-html="field"</div>
  `,
  props: ['params'],
  computed: {
    lblcls: function() {return this.params.lblcls || " rt-fldcls rt-lblcls ";},
    fldcls: function() {return this.params.fldcls || " rt-fldcls rt-lblcls ";},
    lblstyle: function() {return this.params.lblstyle;},
    fldstyle: function() {return this.params.fldstyle;},
    label: function() {return this.params.label;},
    field: function() {
      if (!params.input) {
        return this.params.field;
      } else {
        return Vue.buildInput(params);
      }
    },
  },
});

/** Builds an HTML input based on params:
 *   name: name of the ctrl
 *   val: the value
 *   input: type of input: text, select, checkbox, etc
 *   inpparams: esp. for select
 *     allownull: Can the select be empty? false|true|string
 *     options: object of select opts {val:display, val:display...
 *   placeholder - opt
 *   inpcls - input css class - opt
 *   width: opt - width in px
 *   style: opt - custom input style
 *   inpatts: opt - arbitrary string of attributes to apply to the string
 */
Vue.buildInput = function(params) {
  //console.log("Paramas", params);
  var reginptypes = ['text','password','color','date','datetime-local', 'email',
    'month', 'number', 'range', 'search', 'tel', 'time', 'url', 'week'];
  var defcls = " rt-inp ";
  var name = params.name;
  var val = params.val;
  var placeholder = params.placeholder || '';
  var inpcls = params.inpcls + defcls ;
  var cmnatts = ' name="'+name+'" class="'+inpcls+'" placeholder="'+
          placeholder+'" '+ params.inpatts + ' ';
  var type = params.type;
  //console.log("Build Input: params:",params,"cmnatts:",cmnatts);
  if (reginptypes.indexOf(type) !== -1){ //It's a regular textish input type
    return '<input type="'+type+'" value="'+htmlEncode(val)+'" '+cmnatts+'/>';
  } else if (type === 'select') { // Need inpparams for select:
    console.log("IN Bld Inp; Params:",params,'val',val);
    //allownull: default: true - if string, the string display for empty
    var allownull = params.allownull; //Trueish, falseish, or a string placeholder
    if (allownull === undefined) {
      allownull = true;
    }
    var options = params.options;
    //options: object keyed by value=>display
    var inp = '\n<select '+cmnatts + '>\n';
    var selected = "";
    if (allownull) {
      if (typeof allownull !== 'string') {
        allownull='';
      }
      if (!val) {
        selected = " selected ";
      }
      inp += '  <option value="" '+selected+'>'+allownull+'</options>\n';
      selected = '';
    }
    for (var key in options) {
      if (key === val) {
        selected = " selected ";
      }
      inp += '  <option value="'+key+'" '+selected+'>'+options[key]+'</options>\n';
      selected = '';
    }
    inp += "</select>\n";
    return inp;
  } else if (type === 'checkbox') { //Add hidden empty in case cleared
    var checkedval = params.inpparams.checkedval || "1";
    var inp = '<input type="hidden" name="'+name+'"/>\n';
    var checked = val===checkedval ? " checked " : "";
    inp += '<input type="checkbox" value="'+htmlEncode(checkedval)+'" '+checked+'/>\n';
    return inp;
  } else if (type === 'textarea') { 
    var inp = '\n<textarea '+cmnatts +'>'+val+'</textarea>\n';
  } else {
    throw "Unhandled input type ["+type+"]";
  }
};


/** It should be positioned absolutely within a relatively positioned
 * element representing a model/db object. The containing object should have
 * data- attributes with model, id, & deletable status.
 */
Vue.component('delete-x',{
  name: 'delete-x',
  template: `
  <div data-tootik="Do you want to delete this?" @click="deleteobj">
 <img src='/mixed/img/cross-31176.svg' class="delete-x actionable" 
  
   data-tootik="Delete This?">
  </div>
  `,
  methods: {
    deleteobj() {
        this.$parent.delete(); //Preferred anyway
    },
  },
});
//"C:\www\Laravels\LQP\laravel\public\mixed\img\cross-31176.svg"






/** AJAX delete button for PkModels
 * @props params
 *   classname - required
 *   id - required
 *   btncls - default: btn-cls
 *   url - default: /ajax/delete
 *   cascade - default: false
 *   delfromdom - the row class to remove - eg, '.rt-rowcls'
 */
Vue.component('delete-btn', {
  name: 'delete-btn',
  template: `
   <div :class="btncls"
      @click.stop="del()">
      Delete</div>
`,
  props:['params'],
  computed: {
    btncls: function (){
      return this.params.btncls || " pkmvc-button inline m-v-1 m-h-1";}
  },
  methods: {
    del: function() {
      var delfromdom=this.params.delfromdom;
      if (delfromdom && !(typeof delfromdom === 'string')) {
        delfromdom = ".js-resp-row";
      }
      if (!this.params.classname || !this.params.id) {
        if (delfromdom) {
          $(this.$el).closest(delfromdom).remove();
        }
        return;
      }

      var url = this.params.url || "/ajax/delete";
      var params = {
        model: this.params.classname,
        id: this.params.id,
        cascade: this.params.cascade
      };
      axios.post(url,params).then(response=> {
        if (delfromdom) {
          $(this.$el).closest(delfromdom).remove();
        }
        console.log("\nDelete Success w. response:\n", response);
      }).catch(error=>{
        console.log("\nDelete Failed w. error:\n", error);
      });
    }
  }
}); 


//////////////////  Start Row Based Tables ///////////////
//Two components - the frame, that may hold any number of rows,
//The row - which has a header/label & number of values below it,
//but below a certain width, each field has the label next to it (
// Or above it? Has to be both configurable, and work with bs4
/*
  <div class="d-none d-lg-inline-block">Only above width</div>
  <div class=" d-lg-none">Only BELOW width</div>
*/

/**
 * Shows a row of Labels/Data in the table -- included by resp-tbl
  'celldataarr' is an array of objects containing information about each cell
      field: Value to Display in the cell
      label: Label for the cell, if small viewport
      lblcls: Label CSS for the cell, if small viewport
      fldcls: Field CSS Class
      cellcls: Cell CSS (if small viewport, so includes both label & field
  'rowinfo' - object w. whole-row data:
      delete: {Required: classname: model, id:id 
          optional: url:'/ajax/delete", btncls:'btn-cls', cascade:false,
          delfromdom:??  -- all other celldata opts


      celldefs: object with default values for each cell, if not given  
      rowcls: The CSS class of the row
      islbl: Is a lable row?
  bp:  xs, sm, md, lg, xl
      
// 'bp' = the breakpoint to change the row.`
// The first is probably special, since it is the row headings/labels
// each of which contains info about the item, including possible delete buttons, 
// the ID of the object, and the label for the item.
// First row might just be labels if no data
// 'bp' is xs, sm, md, lg, xl
*/
  //<div :class="rowcls + show_bg_flex_lbl + block_below">
window.Vue.component('resp-row', {
  name: 'resp-row',
  template: `
  <div class="js-resp-row"
      :class="rowcls + show_bg_flex_lbl + display_below +' '+lbl_row_cls">
    <input type='hidden' :name='id_name' :value="rowinfo.id" />
    <input type='hidden' name='model' :value="rowinfo.classname" />
    <div v-for="(celldata,idx) in cmp_celldataarr"
        :class="celldata.cellcls" :style="celldata.cellstyle">
      <div :class="show_sm + ' '+ celldata.lblcls" v-html="celldata.label"></div>
      <div :class="celldata.fldcls" v-html="celldata.field"></div>
    </div>
    <div v-if="del && (del.id || del.delfromdom)" :class="del.cellcls" :style="del.cellstyle">
       <delete-btn :params="del"></delete-btn>
    </div>
    <div v-else-if="del" :class="del.cellcls" :style="del.cellstyle"></div>
  
  </div>
  `,
  props: ['celldataarr','rowinfo', 'bp'],
  computed: {
    id_name: function() {
      return this.rowinfo.relation+'['+this.rowinfo.cnt+'][id]';
    },
    rowcls: function() {return this.rowinfo.rowcls || ' rt-rowcls ';},
    show_sm: function() {return  " d-"+this.bp+"-none ";},
    //show_sm_block: function(){return  " d-"+this.bp+"-block ";},
    display_below: function(){
      if (this.rowinfo.islbl) {
        return  " d-none-below-"+this.bp + " ";
      } else {
        return  " d-block-below-"+this.bp + " ";
      }
    },
    lbl_row_cls: function(){return this.rowinfo.islbl ?
      (this.rowinfo.lbl_row_cls || ' lbl_row_cls ') : '';
    },
    block_below: function(){return  " d-block-below-"+this.bp + " ";},
    none_below: function(){return  " d-none-below-"+this.bp + " ";},
    show_bg_inline: function() {return  " d-none d-"+this.bp+"-inline-block ";},
    show_bg_flex: function() {return  " d-none d-"+this.bp+"-flex ";},
    show_bg_flex_lbl: function() {
      if (this.rowinfo.islbl) {
        return  " d-none d-"+this.bp+"-flex ";
      } else {
        return " ";
      }
    },
    del: function() {
      var del = this.rowinfo.delete;
      if (del === 'undefined') {
        return false;
      }
      var celldatadefs = this.celldefaults();
      var deldata = Object.assign({},celldatadefs,this.rowinfo.celldefs, del);
      deldata.cellstyle=" width: 50px; ";
      return deldata;
    },
    cmp_celldataarr: function() {
      var celldatadefs = this.celldefaults();
      var dataarr = [];
      var me = this;
      this.celldataarr.forEach(function(celldata, idx) {
        if (celldata.width) {
          celldata.cellstyle= " width:"+celldata.width+"px; ";
        }
        dataarr.push(Object.assign({},celldatadefs, me.rowinfo.celldefs,celldata));
      });

      return dataarr;
    }
  },
  methods: {
    celldefaults: function() {
      var cnt = 0;
      var offset = 0;
      this.celldataarr.forEach(function(celldata,idx) {
        if (celldata.width) {
          offset += celldata.width;
        } else {
          cnt++;
        }
      });
      //var cnt = this.celldataarr.length;
      if ('delete' in this.rowinfo) {
        //offset = " "+60/cnt+"px ";
        offset += 60;
      }
      offset = " "+offset/cnt+"px ";
      var pc = 100/cnt;
      var style = " flex-basis: calc("+pc+"% - "+offset+"); ";
      //var style = " flex-basis: "+pc+"%; ";
      if (this.rowinfo.islbl) {
        var fldclass = " rt-fldcls rt-lblcls ";
      } else {
        var fldclass = " rt-fldcls ";
      }
      
      var celldatadefs = {
        cellcls: " rt-cellcls ",
        fldcls: fldclass,
        lblcls: " rt-lblcls ",
        rowcls: " rt-rowcls ",
        cellstyle: style
      };
      return celldatadefs;
    }
  }
});

///////////////////  Table that uses resp-rows above
/** 
 * Composes a responsive table from responsive rows
 * Props - 
 *   tbldata: object containing the table data:
 *     head:Table Header
 *     headcls: Head CSS  class
 *     tblcls: Table Class
 *     bp:  xs, sm, md, lg, xl
 *     rowinfo: (Overridden in rowdataarr)
 *     rowdataarr: array of row objects containing data for each resp-row:
 *      celldataarr: the cell data array for the row
 *      rowinfo
 *    new - opt - array required to make new row
 *        
 *   
 */
window.Vue.component('resp-tbl', {
  name: 'resp-tbl',
//CVue.component('responsive-table', {
  template: `
  <div :class='tblcls' class="js-resp-tbl">
    <input type='hidden' :name='tbldata.relation'>
    <div v-if="tbldata.head" :class="headcls" v-html="tbldata.head"></div>
    <resp-row v-for="(rowdata,idx) in rowdataarr" v-if="!rowdata.rowinfo.new"
        :celldataarr="rowdata.celldataarr"
        :rowinfo="rowdata.rowinfo"
        :bp="bp">
    </resp-row>
    <div v-if="newbtn" >
       <div class="pkmvc-button inline" @click.stop="addrow()">New</div>
    </div>
    <div v-if="savebtn" >
       <div class="pkmvc-button inline" @click.stop="submit()">Save</div>
    </div>
    <div v-if="tbldata.foot" :class="footcls" v-html="tbldata.foot"></div>
    
  </div>
`,
       //<new-btn :params="newbtn"> </new-btn>
  //props: ['head', 'headcls', 'coldata', 'tbldata','tblcls'],
  props: ['tbldata'],
  methods: {
    submit: function(ev) {
      var fd = new FormData(this.$el);
      var jqints = $(this.$el).find(':input');
      jqints.each(function(idx, el) {
        var $el = $(el);
        fd.append($el.attr('name'), $el.val());
      });
      var me = this;
      axios.post(this.savebtn.url, fd).then(response=> {
        console.log("The response was:", response);
      });
    },
    addrow: function() {
      //var lblrow = Object.assign({},this.rowdataarr[this.rowdataarr.length-1]);
      //var cnt = this.rowdataarr.length-1;
      var cnt = this.tbldata.rowdataarr.length-1;
      var lblrow = _.cloneDeep(this.rowdataarr[0]);
      lblrow.celldataarr.forEach(function(celldata,idx) {
        celldata.field = celldata.field.replace(/__CNT_TPL__/g, cnt-1);
      });
      lblrow.rowinfo.cnt = cnt-1;
      lblrow.rowinfo.islbl=false;
      lblrow.rowinfo.new=false;
      this.tbldata.rowdataarr.push(lblrow);
    }
  },

  computed: {
    savebtn: function() {
      var savebtn = this.tbldata.savebtn;
      if (!savebtn) {
        return null;
      }
      return {
        url: savebtn.url || '/ajax/save'
      };

    },
    newbtn: function() {
      if (!this.tbldata.newbtn) {
        return null;
      }
      return this.tbldata.newbtn;
    },
    rowdataarr: function() {
      var rowdataarr = [];
      var me = this;
      this.tbldata.rowdataarr.forEach(function(rowdata,idx) {
        rowdataarr.push(Object.assign({}, {rowinfo:me.tbldata.rowinfo},rowdata));
      });
      return rowdataarr;
    },
    bp: function() {return  this.tbldata.bp || "md";},
    headcls: function() {return this.tbldata.headclass || "rt-headcls";},
    tblcls: function() {return this.tbldata.tblclass || "rt-tblcls";}
  }

  });

//////////////////  End Column Based Tables ///////////////








/******************** END  Reactive Tables ************************/


//////////////  Mixins //////////
window.formatMixin = {
  methods: {
    formatDate: function(dt,fmt) {
      if ((typeof dt === 'object') && (dt !== null)) {
        dt = dt.date;
      }
      if (fmt === "DtTm") {
        fmt = "MMM D YYYY  h:mm A";
      } else if (fmt === "Dt") {
        fmt = "MMM D YYYY";
      } else if (fmt === "Tm") {
        fmt = "h:mm A";
      }
      //"MMM D YYYY  h:mm A" - Nov 2 2018 3:05 PM
      fmt = fmt || "MMMM D, YYYY";
      var m = window.moment(dt);
      if (!m.isValid()) {
        console.log("Invalid date: ",dt);
        return '';
      }
      return m.format(fmt);
      //return m.isValid() ? m.format(fmt) : '';
    },
    formatCurrency: function(amt) { //Returns a wrapped value for negative
      num = Number(amt);
      if (isNaN(num)) {
        console.log("Invalid number: ",amt);
        return ' ';
      }
      if (!num || (num == 0)) {
        return ' ';
      }
      var sign = '';
      var cssclass = ' dollar-value '
      if (num < 0) {
        num = -num;
        sign = '-';
        cssclass += ' negative-dollar-value ';
      }
      return "<div class='"+cssclass+"'>"+sign +'$'+  num.toLocaleString("en");
    },
    formatChecked: function(val) { //If val is string, check if "0"
      if (typeof val === 'string'){
        var tst = parseInt(val);
        if (!isNaN(tst)) {
          val = tst;
        }
      }
      if (val) {
        return  '&#9745;';
      }
      return  '&#9744;';
    },
    formatEmpty: function(val) { //If undefined, empty string
      if (!val) return ' ';
      return val;
    },
    formatFixed: function(val,prec) {
      prec = prec || 2;
      val = Number(val);
      if (isNaN(val)) {
        return '';
      }
      return val.toFixed(prec);
    },
    /*Format a link - val=url, if opt=string, the label, else opt object: {
      label: the label
      class: the CSS class
      atts: Arbitrary attributes (as string)
    */
   formatSSN: function(ssn) {
      ssn = ssn.trim();
      var ssarr = Array.from(ssn);
      if (ssarr.length !== 9) {
        console.error("Invalid SSN: ", ssn);
        return '';
      }
      var ss1 = ssarr.slice(0,3).join('');
      var ss2 = ssarr.slice(3,5).join('');
      var ss3 = ssarr.slice(5,9).join('');
      var ssf = ss1 +'-'+ss2+'-'+ss3;
      return ssf;
   },
       
    formatLink: function(val,opt) { 
      if (!opt) {
        opt = {};
      } else if (typeof opt === 'string') {
        opt = {label:opt};
      }
      return "<a href='"+val+"' class='"+opt.class+"' "+opt.atts+">"+opt.label+"</a>";

    }
  }
};

/////   Table make Mixin //////////

/**
 Assumes caller data:  {
  ids: array of IDs,
  model: PkModelName,
  keymap: Object. keys as att names, & value as label str
     or object of properties- {
        label: Label for the cell, if small viewport
        lblcls: Label CSS for the cell, if small viewport
        fldcls: Field CSS Class
        cellcls: Cell CSS (if small viewport, so includes both label & field
  tbldata: object containing the table data:
      head:Table Header
      headcls: Head CSS  class
      tblcls: Table Class
      bp:  xs, sm, md, lg, xl
      rowinfo: (Overridden in rowdataarr)
  
  url: (opt str) - default: '/ajax/fetchattributes'

*/
window.tableMixin = {
  methods: {
    initData: function() {
      var lblrow = {};
      this.tbldata.bp = this.tbldata.bp || 'md';
      //var tbldata = _.cloneDeep(this.tbldata);
      //var rowinfo = tbldata.rowinfo ?   _.cloneDeep(tbldata.rowinfo): {};
      this.tbldata.rowinfo = this.tbldata.rowinfo ? this.tbldata.rowinfo : {};
      var lblrowinfo =   _.cloneDeep(this.tbldata.rowinfo);
      lblrowinfo.islbl = true;
      var rowdataarr = [];
      //var lblrow = _.cloneDeep(this.rowdataarr[0]);
      var keys = Object.keys(this.keymap);
      var lblcelldataarr = [];
      for (var key in this.keymap) {
        var map = this.keymap[key];
        if (typeof map === 'string') {
          map = {label: map};
        }
        lblrow[key] = Object.assign({},map,{field:map.label});
        lblcelldataarr.push(lblrow[key]);
        this.keymap[key] = map;
      }
      rowdataarr.push({celldataarr:lblcelldataarr,rowinfo:lblrowinfo,bp:this.tbldata.bp});
      //We made the first label row.
      var url = this.url || '/ajax/fetchattributes';
      var data = {id:this.ids, model:this.model,keys:keys};
      axios.post(url,data).
        then(response=>{
          console.log("The response data:",response.data);
          response.data.forEach(row=>{
            var keymap = _.cloneDeep(this.keymap);
            var celldataarr = [];
            for (var akey in row) {
              if (keymap[akey].format) {
                var format = keymap[akey].format;
                keymap[akey].field = this[format](row[akey],keymap[akey].formatopts);
              } else {
                keymap[akey].field = row[akey];
              }
              celldataarr.push(keymap[akey]);
            }
            rowdataarr.push({celldataarr:celldataarr,rowinfo:this.tbldata.rowinfo});
          });
          this.tbldata.rowdataarr=rowdataarr;
          console.log("The tbldata:",this.tbldata);
        }).
        catch(error=>{console.error("Error: ",error,error.response);});
    },
    
  },
};

//Simple stuff, like non-null object, & not empty
window.utilityMixin = {
  methods: {
    isObject(tst) { 
      if ((typeof tst === 'object') && (tst !== null)) {
        return tst;
      }
      return false;
    },
//Probably better than below. Data can use sensible defaults, & only params that have keys can override first from settings, second from params
    overrideDefaults: function(settings) {
      if (!this.isObject(settings)) {
        settings = {};
      }
      if (!this.isObject(this.params)) {
        this.params = {};
      }
      var datakeys = Object.keys(this.$data);
      datakeys.forEach((key,idx) => {
        if (key in settings) {
          this[key] = settings[key];
        } else if (key in this.params) {
          this[key] = this.params[key];
        }
      });
    },
    //Called from mounted, defaults an object w. param keys & defaults to set data to
    // Assumes "params" in props
    initDataFromParams(defaults) {
      for (var key in defaults) {
        this[key] = this.params[key] || defaults[key];
      }
      var lblwidth = this.params.lblwidth || defaults.lblwidth;
      if (lblwidth && this.label) {
        this.lblstyle = this.lblstyle + " width: "+lblwidth+"; ";
      }
      //Do same for input width, like checkbox:
      var inpwidth  = this.params.inpwidth || defaults.inpwidth;
      if (inpwidth) {
        this.inpstyle += " width: "+inpwidth+"; ";
      }
    },
    showThisFromDefaults(defaults,lbl) {
      if (!defaults) defaults=this.defaults;
      var vals = {};
      for (var key in defaults) {
        vals[key] = this[key];
      }
      console.log(lbl+ " Current this values:",vals);
      return vals;
    }
  }
};

/** For a stand-alone Vue instance that pops up a Modal submission form, &
 * then wants to update other data on the page based on the changes
 * See top of file for docs, and
 * LQP/apps/resources/... activeprofile.blade.php, PostComponents.vue,
 *  & post_button.phtml for examples
 */
window.refreshRefsMixin = {
  methods: { // (But this is also in the mixin reloadRefsMixin)
    setReloadRefs: function (reloadrefs) {
      if (reloadrefs) {
        if (this.$refs && this.$refs.modal_wrapper
              && this.$refs.modal_wrapper.setReloadRefs) {
          this.$refs.modal_wrapper.setReloadRefs(reloadrefs);
        }
        this.postparams.reloadrefs = this.postparams.reloadrefs.concat(reloadrefs);
      }
    }
  }
};

/****
 * //////////   Start of individual inputs/controls that submit/fetch AJAX directly
 * // Input/TextArea - as soon as lose focus, select as soon as select, checkbox
 * // as soon as check, etc.
 * 
 * 
 * Assume all expect their main "props" is "params". They use the inputMixin,
 * as well a utility mixin, & possbily format Mixn. They have aside from the 
 * value parameters, some standards for their appearance. There are generally
 * 2-3 elements - the input, (optional label div) and a div wrapper.
 * Each (Wrapper, label div, & input, have a default CSS Class, as well as suppimentary 
 * classes AND styles provided by the params. The p
 */
// Common mixin for all immediate inputs
window.inputMixin = {
  /**
   * 'params' object 
   *   model:
   *   id:
   *   name:
   *   value:
   *   tooltip: (opt: Tooltip for intput)
   *   submiturl: (opt: default: "/ajax/submit")
   *   fetchurl: (opt: default: "/ajax/fetchattributes"
   *   formatin: (opt - format method to show the data)
   *   formatout (opt - format method to submit the data)
   *   inpcss(opt - css class for the input)
   *   type(opt - input type)
   *   ownermodel(opt)
   *   ownerid (opt)
   *   label: (opt - label for control - then will be wrapped)
   *   lblcss:  (opt - css class for input)
   *   wrapcls:  (opt - css class for input)
   *   lblstyle: (opt -custom styles for the element)
   *   wrapstyle: (opt -custom styles for the element)
   *   inpstyle: (opt -custom styles for the element)
   *   @atts & noInherit: Possibe way to inject arbitrary atts into an el?
   * @type object
   */
  props: ['params'],
  data() {
      var defaults = {
        name: null, id: null, ownermodel: null, type: null, ownerid: null, 
        model: null, inpcss: '', formatin: null, formatout: null, lblcss: '',
        label: null, tooltip: '', submiturl:"/ajax/submit",
        fetchurl: "/ajax/fetchattributes", attribute: null, foreignkey: null,
        wrapcss: '', inpstyle:'', wrapstyle:'', lblstyle:'',value: null,
        checked:null, options:[],checkedvalue:1,uncheckedvalue:0,
        lblwidth: null, inpwidth:null}; //If input or label width is limited, 
         //the other element can take the space. 
    var data = defaults;
    data.defaults = defaults;
    console.log("INIT DATA:",data);
    return data;
    /*
    return {
      name:null,
      value: null,
      id: null,
      ownermodel: null,
      ownerid: null,
      model: null,
      inpcss: null,
      formatin: null,
      formatout: null,
      lblcss: null,
      label: null,
      tooltip: null,
      submiturl: null,
      fetchurl: null,
      attribute: null,
      foreignkey: null,
      wrpcss: null,


    };
    */

  },
  mounted() {
      console.log("Mounted AJaxTextInput, defaults:",this.defaults,"Params:",this.params);
      this.initDataFromParams(this.defaults);
      this.initData();

  },
  methods: {
    //Special for checkboxes - usually true/false - & if not checked sends nothing,
    //so not false. This will both toggle the appearance of the checkbox, AND send
    //the unchecked value, so can clear
    setCheckedState() {
      if(this.value == this.checkedvalue) {
        this.checked = true;
      } else {
        this.checked = false;
      }
    },
    toggleCheckState(event,arg) {
      console.log("Got change event?",event,arg,"this.val",this.value,"Checkedval:", this.checkedvalue,"Unchecked:", this.uncheckedvalue);
      if(this.value == this.checkedvalue) {
        this.value = this.uncheckedvalue;
        this.checked = false;
      } else {
        this.value = this.checkedvalue;
        this.checked = true;
      }
      console.log("After toggle state, this.checked:",this.checked,"value",this.value);
    },
    initData() {
    
      this.showThisFromDefaults(this.defaults,"InpCtl About to fetch");
      axios.post(this.fetchurl,
      {model:this.model,
       id:this.id,
       ownermodel:this.ownermodel,
       ownerid:this.ownerid,
       keys: this.name,
     }).then(response=>{
       console.log("Succeeded in fetch, resp:", response);
       var value = response.data[this.name];
       if (this.formatin) {
         value = this[this.formatin](value);
       }
       this.value = value;
       if (this.value == this.checkedvalue) {
         this.checked=true;
       } else {
         this.checked = false;
       }
       var defaults = this.defaults;
       var curr = {};
       
       for (var key in defaults) {
         curr[key] = this.key;
       }
       console.log("After inits, the current data:",curr);
     }).
      catch(error=>{console.error("Failed to fetch:",error.response, error);});
    },
    savesubmit(event,arg) {
      console.log ("Save submit event",event,"Arg:",arg);
      this.showThisFromDefaults(this.defaults,"InpCtl About to submit");
      if (this.formatout) {
        this.value = this[this.formatout](this.value);
        //var fields = {[keys]:val
      }
      axios.post(this.submiturl,
      {model:this.model,
       id:this.id,
       ownermodel:this.ownermodel,
       ownerid:this.ownerid,
       foreignkey: this.foreignkey,
       attribute: this.attribute,
       fields: {[this.name]:this.value},
     }).then(response=>{
       console.log("Succeeded in submit resp:", response);
       var data = response.data;
       this.id = data.id;
       var value = data[this.name];
       if (this.params.formatin) {
         value = this[formatin](value);
       }
       this.value = value;
     }).
      catch(error=>{console.error("Failed to save submit/post/update:",error.response, error);});
    },
    }
    

  };

window.Vue.component('ajax-text-input', {
  name: 'ajax-text-input',
  mixins: [window.utilityMixin, window.inputMixin, window.formatMixin],
  /*
        name: null, id: null, ownermodel: null, type: null, ownerid: null, 
        model: null, inpcss: '', formatin: null, formatout: null, lblcss: '',
        label: null, tooltip: '', submiturl:"/ajax/submit",
        fetchurl: "/ajax/fetchattributes", attribute: null, foreignkey: null,
        wrapcss: ''};
        */
  template: `
  <div class='ajax-wrap-css' :class="wrapcss" :style="wrapstyle">
    <div v-if="label" :style="lblstyle" class='ajax-lbl-css' :class="lblcss" v-html="label"></div>
    <input :style="inpstyle" :type="type"
       @esc="false"
       @tab="savesubmit($event,'tab')"
       @enter="savesubmit($event,'enter')"
       @keyup.enter="savesubmit($event,'EnterKeyUp on TextInput')" 
       @blur="savesubmit($event,'blur')"
       v-model="value" :name="name" :class="inpcss" class="ajax-inp-css">
  </div>`,
});


window.Vue.component('ajax-textarea-input', {
  name: 'ajax-textarea-input',
  mixins: [window.utilityMixin, window.inputMixin, window.formatMixin],
  /*
        name: null, id: null, ownermodel: null, type: null, ownerid: null, 
        model: null, inpcss: '', formatin: null, formatout: null, lblcss: '',
        label: null, tooltip: '', submiturl:"/ajax/submit",
        fetchurl: "/ajax/fetchattributes", attribute: null, foreignkey: null,
        wrapcss: ''};
        */
  template: `
  <div class='ajax-wrap-css' :class="wrapcss" :style="wrapstyle">
    <div v-if="label" :style="lblstyle" class='ajax-lbl-css' :class="lblcss" v-html="label"></div>
    <textarea :style="inpstyle" :type="type"
       @esc="false"
       @tab="savesubmit($event,'tab')"
       @enter="savesubmit($event,'enter')"
       @keyup.enter="savesubmit($event,'EnterKeyUp on TextInput')" 
       @blur="savesubmit($event,'blur')"
       v-model="value" :name="name" :class="inpcss" class="ajax-inp-css"></textarea>
  </div>`,
});



/**AJAX Load & Save Select */
//options is an array of objects: {value:value,label:label, rendered by:'
//<option v-for="(option, idx) in options" :value="option.value" v-html="option.label"></option>
//CVue.component('pk-select-arr', {
Vue.component('ajax-select-input',{
  name: 'ajax-select-input',
  mixins: [window.utilityMixin, window.inputMixin, window.formatMixin],
  type: 'select',
  template: `
  <div class='ajax-wrap-css' :class="wrapcss" :style="wrapstyle">
    <div v-if="label" class='ajax-lbl-css' :class="lblcss" :style="lblstyle" v-html="label"></div>
  <select 
    @change="savesubmit($event,'Selected Select')" 
    @keyup.enter="savesubmit($event,'EnterKeyUp on Select')" 
    class="ajax-select-css" :name="name" :class="inpcss" :style="inpstyle" v-model="value">
      <option v-for="(option, idx) in options" :value="option.value" v-html="option.label">
      </option>
    </select>
  </div>
`,
  });

Vue.component('ajax-checkbox-input',{
  name: 'ajax-checkbox-input',
  mixins: [window.utilityMixin, window.inputMixin, window.formatMixin],
  type: 'checkbox',
  template: `
  <div class='ajax-wrap-css' :class="wrapcss" :style="wrapstyle">
    <div v-if="label" class='ajax-chcbxlbl-css' :class="lblcss" :style="lblstyle" v-html="label">
    </div>
    <input type="checkbox" 
      @click="changesavesubmit($event,'Clicked')" 
      class="ajax-chcbxinp-css" :name="name" :class="inpcss" :style="inpstyle"
           v-model="checked" :value="value" >
  </div>
`
  /*
           v-model="checked" :value="value" @change="toggleCheckState($event,'toggleCheckbox')" >
  */
  ,
  methods: {
    changesavesubmit(event,action) {
      console.log("In changesavesubmit");
      this.toggleCheckState(event,'changesavesubmit');
      this.savesubmit(event, 'changesavesubmit');
    },
  },
  /*
  <input type="hidden" :name="name" value="0">
    @change="savesubmit($event,'Clicked')" 
   * 
   */
  

  computed: {
    /*
    checked() {
      return !!this.value;
    }
    */
  },
});

/** Automates some gathering & submitting of ajax data. 
 * @method fetchData fetches input data from within an enclosing '.input-container',
 * adds any optional params passed in, & returns the form data object.
 * @method submitData takes a url & form data & posts it, then calls the method
 *    this.processResponse(data) in the implementing component. 
 *  @method autoSubmit takes optional url & params - default Url is /ajax/sumbit
 *    it passes the params to fetchData, then performs the ajax call.
 *    All the implementing component needs to do is create the form & 
 *    implement the response handler.
 * @type Vue mixin
 */
window.ajaxpostingMixin = {
  methods: {
    fetchDataNoFD: function(params) {
      var emptyobj = {};
      var el = this.$el;
      $(el).closest(".input-container").find(":input").each(function(idx, inp) {
        console.log("The el of the posting comp is:", el);
        // Base data collection
        $inp =  $(inp);
        var key =$inp.attr('name'); 
        var val = $inp.val(); 
        emptyobj[key] =val;
      });
      if (params !== null && typeof params === 'object') {
        for (var akey in params) {
          emptyobj[akey] = params[akey];
        }
      }
      console.log ("The fetch raw key/vals: ", emptyobj);
      return emptyobj;
    },
    fetchData: function(params) {
      var fd = new FormData();
      var el = this.$el;
      var me = this;
      console.log ("Mixin el is:",el);
      //Find the nearest "form container", then all the inputs
      var emptydata = {};
       $(el).closest(".input-container").find(":input").each(function(idx, inp) {
          var $inp = $(inp);
          //fd.append($inp.attr('name'),$inp.val());
          emptydata[$inp.attr('name')]=$inp.val();
      }); 

      //Add any extra data in params
      if (params && (typeof params === 'object')) {
        for (var key in params) {
//          fd.append(key,params[key]);
           emptydata[key] = params[key];
        }
      }
      return emptydata;
    },

    submitData: function (url,fd) {
      axios.post(url,fd).
        then(response=>{
          var data = response.data;
          if (this.processResponse && (typeof this.processResponse === 'function')) {
            this.processResponse(data);
          }
          this.notifyUpdate();
        }).catch(error=>{console.error("Error submiting:",error.response, error);});
    },

    autoSubmit: function(url,params) {
      if (!url) {
        url = "/ajax/submit";
      }
      var fd = this.fetchData(params);
      this.submitData(url,fd);
    },
    notifyUpdate: function(refs) { //Calls other componentes (either args or properties) to re-init
      if (this.params.reloadrefs) {
        var reloadrefs = this.params.reloadrefs;
        if (!Array.isArray(reloadrefs)) {
          reloadrefs = [reloadrefs];
        }
        reloadrefs.forEach(function(el) {
          if (el.initData) {
            el.initData();
          }
        });
      }
      /*
      if (!refs) {
        refs = this.refs;
      }
      if (!refs) {
        return;
      }
      if (!Array.isArray(refs)) {
        refs = [refs];
      }
      console.log("In notify update, refs :",refs);
      refs.forEach( (cmp, idx) => {
        console.log("In Loop, cmp:",cmp);
        this.$root.refresh(cmp);
        //cmp.initData();
      });
    },
    */
  },
},
};