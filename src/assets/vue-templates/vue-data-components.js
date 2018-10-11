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


//Wraps both the button & modal - the modal is a single file component pk-modal.vue
//Should take 3 prop objects - "btnparams" (for the button itself - title, appearance)
//, "modalparams" (For the modal box - size, title, etc)
// 'contentparams' - what's inside the modal
window.Vue.component('pk-modal-wrapper',{
  name: 'pk-modal-wrapper',
  template:`
  <!--
    <component v-bind="{paramsx:paramsx}" :is="dynamiccomp"></component>
  -->
  <div>
    <pk-modal-btn :btnparams="btnparams"
      @click="callOwner(event,'button') "
       class="btn primary-btn" :class="btnparams.btnCls">
    </pk-modal-btn>


    <pk-modal ref="mymodal" v-if="showModal" @close="showModal=false"
      :modalparams="modalparams" :contentparams="contentparams" :showModal="showModal" >
    </pk-modal>
  </div>
  `,
  props:["contentparams","modalparams","btnparams"],
  methods : {
    callOwner: function() {
      console.log ("Wrapper got called when clicked on "+who);
    },
  },
  data: function() {
    return {showModal: false};
  },
  mounted: function() {
    console.log("PkMdlWrp: contentparams:", this.contentparams,'btn',this.btnparams,'mdl',
    this.modalparams);
  }
});


/** Pops up a pk-modal component - DON'T USE DIRECTLY - USE pk-modal-wrapper! */

//the button - and send param / key to know which modal to open... unless
//the modal is generic, & the button passes everything to it?
//Create 3rd component that wraps both & provides data
  //<div style="z-index: 2000;" @click="doshowModal" class="btn btn-primary btn-pop m-5 p-f fs-8"
window.Vue.component('pk-modal-btn',{
  template: `
  <div style="z-index: 2000;" @click="onClick" class="btn btn-primary btn-pop m-5 p-f fs-8"
   :class="btnparams.popBtnCls"> {{btnparams.label}} </div>
  `,
   props: ['btnparams'],
  mounted: function() {
    console.log("PkModalBtn, btnparams",this.btnparams);
   },
   methods: {
    onClick: function() {
      this.$parent.showModal = true;
      console.log("btn got clicked");
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
    <div :class="lblcls" :lblstyle="lblstyle" v-html="label"></div>
    <div :class="fldcls" :fldstyle="fldstyle" v-html="field"</div>
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
        return '';
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
    //Called from mounted, defaults an object w. param keys & defaults to set data to
    // Assumes "params" in props
    initDataFromParams(defaults) {
      for (var key in defaults) {
        this[key] = this.params[key] || defaults[key];
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


/****
 * //////////   Start of individual inputs/controls that submit/fetch AJAX directly
 * // Input/TextArea - as soon as lose focus, select as soon as select, checkbox
 * // as soon as check, etc.
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
   *   cssclass(opt - css class)
   *   type(opt - input type)
   *   ownermodel(opt)
   *   ownerid (opt)
   *   cssclass: (opt - css class for input)
   *   label: (opt - label for control - then will be wrapped)
   *   lblcss: 
   * @type object
   */
  props: ['params'],
  data() {
      var defaults = {
        name: null, id: null, ownermodel: null, type: null, ownerid: null, 
        model: null, inpcss: '', formatin: null, formatout: null, lblcss: '',
        label: null, tooltip: '', submiturl:"/ajax/submit",
        fetchurl: "/ajax/fetchattributes", attribute: null, foreignkey: null,
        wrapcss: '', inpstyle:'', wrapstyle:'', lblstyle:'',value: null};
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
       var defaults = this.defaults;
       var curr = {};
       
       for (var key in defaults) {
         curr[key] = this.key;
       }
       console.log("After inits, the current data:",curr);
     }).
      catch(error=>{console.error("Failed to fetch:",error.response, error);});
    },
    postsubmit() {
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
      catch(error=>{console.error("Failed to submit/post/update:",error.response, error);});
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
  <div class='inp-wrap' :class="wrapcss" :style="wrapstyle">
    <div v-if="label" :style="lblstyle" class='inplblclass' :class="lblcss" v-html="label"></div>
    <input :style="inpstyle" :type="type" @blur="postsubmit" v-model="value" :name="name" :class="inpcss" class="pk-inp">
  </div>`,
});